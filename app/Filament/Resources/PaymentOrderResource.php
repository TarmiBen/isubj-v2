<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentOrderResource\Pages;
use App\Models\PaymentOrder;
use App\Models\PaymentConcept;
use App\Models\Student;
use App\Filament\Support\PaymentOrderHistory;
use App\Filament\Support\StudentColumn;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentOrderResource extends Resource
{
    protected static ?string $model = PaymentOrder::class;
    protected static ?string $navigationIcon  = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationLabel = 'Adeudos';
    protected static ?string $modelLabel      = 'Adeudo';
    protected static ?string $pluralModelLabel = 'Adeudos';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_payment::order');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información')->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Alumno')->required()
                    ->relationship('student', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->student_number . ')')
                    ->searchable()->preload(),
                Forms\Components\Select::make('payment_concept_id')
                    ->label('Concepto')->required()
                    ->options(PaymentConcept::active()->pluck('name', 'id'))
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $concept = PaymentConcept::find($state);
                            $set('subtotal', $concept?->default_amount);
                        }
                    }),
                Forms\Components\TextInput::make('folio')->label('Folio')->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('Montos')->schema([
                Forms\Components\TextInput::make('subtotal')
                    ->label('Subtotal')->required()->numeric()->prefix('$')
                    ->default(0)->live(debounce: 500),
                Forms\Components\TextInput::make('discount_amount')
                    ->label('Descuento')->numeric()->prefix('$')
                    ->default(0)->live(debounce: 500),
                Forms\Components\TextInput::make('tax_amount')
                    ->label('IVA')->numeric()->prefix('$')
                    ->default(0)->live(debounce: 500),
                Forms\Components\Placeholder::make('_total_preview')
                    ->label('Total calculado')
                    ->content(fn (\Filament\Forms\Get $get): string =>
                        '$' . number_format(
                            max(0, (float)($get('subtotal') ?? 0)
                                 - (float)($get('discount_amount') ?? 0)
                                 + (float)($get('tax_amount') ?? 0)),
                            2
                        )
                    ),
                Forms\Components\TextInput::make('paid_amount')
                    ->label('Pagado')->numeric()->prefix('$')
                    ->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('balance')
                    ->label('Saldo')->numeric()->prefix('$')
                    ->disabled()->dehydrated(false),
            ])->columns(3),

            Forms\Components\Section::make('Fechas')->schema([
                Forms\Components\DatePicker::make('due_date')->label('Fecha vencimiento')->required(),
                Forms\Components\DatePicker::make('period_start')->label('Período inicio'),
                Forms\Components\DatePicker::make('period_end')->label('Período fin'),
            ])->columns(3),

            Forms\Components\Section::make('Estado')->schema([
                Forms\Components\Select::make('status')->label('Estado')
                    ->options([
                        'pending'      => 'Pendiente',
                        'partial'      => 'Parcial',
                        'paid'         => 'Pagado',
                        'overdue'      => 'Vencido',
                        'cancelled'    => 'Cancelado',
                        'in_agreement' => 'En convenio',
                    ])->required()->default('pending'),
                Forms\Components\Textarea::make('notes')->label('Notas')->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student.activeAgreements', 'concept', 'parentOrder']))
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable(),
                StudentColumn::make(),
                Tables\Columns\TextColumn::make('concept.name')->label('Concepto')->searchable(),
                Tables\Columns\IconColumn::make('is_surcharge')->label('Recargo')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus-small')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (PaymentOrder $record) => $record->is_surcharge
                        ? "Interés {$record->surcharge_rate}% sobre " . ($record->parentOrder?->folio ?? 'adeudo original')
                        : null),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('MXN')->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')->label('Pagado')->money('MXN'),
                Tables\Columns\TextColumn::make('balance')->label('Saldo')->money('MXN')->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('due_date')->label('Vence')->sortable()
                    // Muestra el vencimiento efectivo: con convenio, la fecha corre
                    // por los días extra concedidos.
                    ->formatStateUsing(fn (PaymentOrder $record) => $record->effectiveDueDate()?->format('d/m/Y') ?? '—')
                    ->description(fn (PaymentOrder $record) => $record->agreementExtraDays() > 0
                        ? 'Convenio +' . $record->agreementExtraDays() . ' días'
                        : null)
                    ->color(fn (PaymentOrder $record) => $record->agreementExtraDays() > 0 ? 'info' : null),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'partial',
                        'success' => 'paid',
                        'danger'  => 'overdue',
                        'gray'    => 'cancelled',
                        'primary' => 'in_agreement',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending'      => 'Pendiente',
                        'partial'      => 'Parcial',
                        'paid'         => 'Pagado',
                        'overdue'      => 'Vencido',
                        'cancelled'    => 'Cancelado',
                        'in_agreement' => 'En convenio',
                        default        => $state,
                    }),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')
                    ->options(['pending'=>'Pendiente','partial'=>'Parcial','paid'=>'Pagado','overdue'=>'Vencido','cancelled'=>'Cancelado']),
                Tables\Filters\SelectFilter::make('payment_concept_id')->label('Concepto')
                    ->relationship('concept', 'name'),
                Tables\Filters\Filter::make('overdue')
                    ->label('Vencidos')
                    // Cuenta los días extra del convenio vigente del alumno.
                    ->query(fn (Builder $q) => $q
                        ->whereIn('status', ['pending', 'partial', 'overdue'])
                        ->whereRaw(
                            'DATE_ADD(payment_orders.due_date, INTERVAL COALESCE(('
                            . 'SELECT a.extra_days FROM agreements a'
                            . '  WHERE a.student_id = payment_orders.student_id'
                            . "    AND a.status = 'active'"
                            . "    AND a.type IN ('credit_extension','both')"
                            . '    AND a.extra_days > 0 AND a.deleted_at IS NULL'
                            . '  ORDER BY a.created_at DESC LIMIT 1'
                            . '), 0) DAY) < CURDATE()'
                        )),
                Tables\Filters\TernaryFilter::make('is_surcharge')
                    ->label('Recargos por mora')
                    ->placeholder('Todos los adeudos')
                    ->trueLabel('Solo recargos')
                    ->falseLabel('Sin recargos'),
                Tables\Filters\Filter::make('due_date')
                    ->label('Rango de vencimiento')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Vence desde'),
                        Forms\Components\DatePicker::make('until')->label('Vence hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) $indicators[] = 'Vence desde ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        if ($data['until'] ?? null) $indicators[] = 'Vence hasta ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label('Pagar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (PaymentOrder $record) => in_array($record->status, ['pending', 'partial', 'overdue', 'in_agreement']))
                    ->modalHeading(fn (PaymentOrder $record) => "Registrar pago — Folio {$record->folio}")
                    ->modalSubmitActionLabel('Registrar pago')
                    ->fillForm(fn (PaymentOrder $record) => [
                        'payment_date'   => now()->format('Y-m-d'),
                        'amount_applied' => $record->balance,
                    ])
                    ->form(fn (PaymentOrder $record) => PaymentService::paymentFormSchema($record))
                    ->action(function (PaymentOrder $record, array $data): void {
                        try {
                            $result = PaymentService::registerPayment($record, $data);
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()->title('Monto inválido')->body($e->getMessage())->danger()->send();
                            return;
                        }

                        PaymentService::notifyPaymentRegistered($result);
                    }),
                PaymentOrderHistory::tableAction(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist->schema([
            \Filament\Infolists\Components\ViewEntry::make('history')
                ->hiddenLabel()
                ->columnSpanFull()
                ->view('filament.payments.order-history-entry'),
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['folio'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return "{$record->folio} — " . ($record->student?->full_name ?? '');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPaymentOrders::route('/'),
            'create' => Pages\CreatePaymentOrder::route('/create'),
            'edit'   => Pages\EditPaymentOrder::route('/{record}/edit'),
            'view'   => Pages\ViewPaymentOrder::route('/{record}'),
        ];
    }
}
