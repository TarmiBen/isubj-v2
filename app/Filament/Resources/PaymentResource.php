<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentOrder;
use App\Filament\Support\StudentColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components as InfolistComponents;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Pagos';
    protected static ?string $modelLabel      = 'Pago';
    protected static ?string $pluralModelLabel = 'Pagos';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 21;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_payment');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del pago')->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Alumno')->required()
                    ->relationship('student', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->student_number . ')')
                    ->searchable()->preload()->live(),
                Forms\Components\Select::make('payment_method_id')
                    ->label('Método de pago')->required()
                    ->options(PaymentMethod::active()->pluck('name', 'id')),
                Forms\Components\TextInput::make('folio')->label('Folio')->disabled(),
                Forms\Components\DateTimePicker::make('payment_date')
                    ->label('Fecha de pago')->required()->default(now()),
                // Sin ->live(): ningún campo del formulario depende de este valor
                // (solo se usa al guardar, en CreatePayment). Con live, el blur al
                // pulsar "Guardar" redibujaba el form y se perdía el primer clic.
                Forms\Components\TextInput::make('amount_received')
                    ->label('Monto recibido')->required()->numeric()->prefix('$'),
                Forms\Components\TextInput::make('receipt_number')->label('No. recibo')->nullable(),
            ])->columns(3),

            Forms\Components\Section::make('Adeudos a cubrir')
                ->description('Selecciona los adeudos pendientes del alumno y el monto a aplicar en cada uno')
                ->schema([
                    Forms\Components\Repeater::make('order_payments')
                        ->label('Asignación a adeudos')
                        ->schema([
                            Forms\Components\Select::make('payment_order_id')
                                ->label('Adeudo')
                                ->options(fn (\Filament\Forms\Get $get) =>
                                    PaymentOrder::where('student_id', $get('../../student_id'))
                                        ->whereIn('status', ['pending', 'partial', 'overdue'])
                                        ->with('concept')
                                        ->get()
                                        ->mapWithKeys(fn ($o) => [
                                            $o->id => "{$o->folio} — {$o->concept->name} | Saldo: \$" . number_format($o->balance, 2)
                                        ])
                                )
                                ->live()
                                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                    if ($state) {
                                        $order = PaymentOrder::find($state);
                                        if ($order) {
                                            $set('amount_applied', (float) $order->balance);
                                        }
                                    }
                                })
                                ->searchable()->required()
                                ->helperText(fn (\Filament\Forms\Get $get) =>
                                    empty($get('../../student_id'))
                                        ? '⚠ Primero selecciona un alumno'
                                        : null
                                ),
                            Forms\Components\TextInput::make('amount_applied')
                                ->label('Monto a aplicar')->required()->numeric()->prefix('$')
                                ->helperText('Se llena automáticamente con el saldo del adeudo'),
                        ])
                        ->columns(2)
                        ->addActionLabel('+ Agregar adeudo')
                        ->defaultItems(0),
                ]),

            Forms\Components\Section::make('Referencia bancaria')
                ->schema([
                    Forms\Components\TextInput::make('reference.reference_number')->label('No. referencia')->nullable(),
                    Forms\Components\TextInput::make('reference.bank')->label('Banco')->nullable(),
                    Forms\Components\FileUpload::make('reference.receipt_path')
                        ->label('Comprobante')->directory('payment-receipts')->nullable(),
                ])
                ->columns(3)
                ->collapsible(),

            Forms\Components\Section::make('Notas')->schema([
                Forms\Components\Textarea::make('notes')->label('Notas')->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'method', 'receivedBy', 'orders']))
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable(),
                StudentColumn::make(),
                Tables\Columns\TextColumn::make('method.name')->label('Método')->searchable(),
                Tables\Columns\TextColumn::make('receipt_number')->label('Recibo')
                    ->searchable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_received')->label('Recibido')->money('MXN')->sortable(),
                Tables\Columns\TextColumn::make('amount_applied')->label('Aplicado')->money('MXN'),
                Tables\Columns\TextColumn::make('coverage_label')->label('Tipo')
                    ->badge()
                    ->color(fn (Payment $record) => $record->coverage_color),
                Tables\Columns\TextColumn::make('orders.folio')->label('Adeudos')
                    ->badge()
                    ->separator(',')
                    ->limitList(2)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('payment_date')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')
                    ->colors([
                        'success' => 'applied',
                        'warning' => 'partial',
                        'gray'    => 'pending',
                        'danger'  => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'applied'   => 'Aplicado',
                        'partial'   => 'Parcial',
                        'pending'   => 'Pendiente',
                        'cancelled' => 'Cancelado',
                        'refunded'  => 'Reembolsado',
                        default     => $state,
                    }),
                Tables\Columns\TextColumn::make('receivedBy.name')->label('Cajero')->searchable(),
            ])
            ->defaultSort('payment_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')
                    ->options(['applied'=>'Aplicado','partial'=>'Parcial','pending'=>'Pendiente','cancelled'=>'Cancelado']),
                Tables\Filters\SelectFilter::make('payment_method_id')->label('Método')
                    ->relationship('method', 'name'),
                Tables\Filters\Filter::make('payment_date')
                    ->label('Rango de fecha')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('payment_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('payment_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) $indicators[] = 'Desde ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        if ($data['until'] ?? null) $indicators[] = 'Hasta ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistComponents\Section::make('Datos del pago')
                ->schema([
                    InfolistComponents\TextEntry::make('folio')->label('Folio'),
                    InfolistComponents\TextEntry::make('student.full_name')->label('Alumno'),
                    InfolistComponents\TextEntry::make('method.name')->label('Método de pago'),
                    InfolistComponents\TextEntry::make('payment_date')->label('Fecha de pago')->dateTime('d/m/Y H:i'),
                    InfolistComponents\TextEntry::make('amount_received')->label('Monto recibido')->money('MXN'),
                    InfolistComponents\TextEntry::make('amount_applied')->label('Monto aplicado')->money('MXN'),
                    InfolistComponents\TextEntry::make('change_amount')->label('Cambio')->money('MXN'),
                    InfolistComponents\TextEntry::make('receipt_number')->label('No. de recibo')->placeholder('—'),
                    InfolistComponents\TextEntry::make('coverage_label')->label('Tipo de pago')
                        ->badge()
                        ->color(fn (Payment $record) => $record->coverage_color),
                    InfolistComponents\TextEntry::make('status')->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'applied'   => 'Aplicado',
                            'partial'   => 'Parcial',
                            'pending'   => 'Pendiente',
                            'cancelled' => 'Cancelado',
                            'refunded'  => 'Reembolsado',
                            default     => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'applied' => 'success',
                            'partial' => 'warning',
                            'pending' => 'gray',
                            default   => 'danger',
                        }),
                    InfolistComponents\TextEntry::make('receivedBy.name')->label('Cajero'),
                    InfolistComponents\TextEntry::make('notes')->label('Notas')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),

            InfolistComponents\Section::make('Referencia bancaria')
                ->schema([
                    InfolistComponents\TextEntry::make('reference.reference_number')->label('Referencia'),
                    InfolistComponents\TextEntry::make('reference.bank')->label('Banco'),
                    InfolistComponents\TextEntry::make('reference.is_verified')->label('Verificado')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')
                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                ])
                ->columns(3)
                ->visible(fn (Payment $record) => $record->reference !== null),

            InfolistComponents\Section::make('Adeudos cubiertos por este pago')
                ->schema([
                    InfolistComponents\RepeatableEntry::make('orderPayments')
                        ->label('')
                        ->schema([
                            InfolistComponents\TextEntry::make('paymentOrder.folio')->label('Folio del adeudo'),
                            InfolistComponents\TextEntry::make('paymentOrder.concept.name')->label('Concepto'),
                            InfolistComponents\TextEntry::make('amount_applied')->label('Aplicado en este pago')->money('MXN'),
                            InfolistComponents\TextEntry::make('paymentOrder.total')->label('Total del adeudo')->money('MXN'),
                            InfolistComponents\TextEntry::make('paymentOrder.balance')->label('Saldo actual')
                                ->money('MXN')
                                ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                            InfolistComponents\TextEntry::make('paymentOrder.status')->label('Estado actual')
                                ->badge()
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'pending'      => 'Pendiente',
                                    'partial'      => 'Parcial',
                                    'paid'         => 'Pagado',
                                    'overdue'      => 'Vencido',
                                    'cancelled'    => 'Cancelado',
                                    'in_agreement' => 'En convenio',
                                    default        => $state,
                                })
                                ->color(fn ($state) => match ($state) {
                                    'paid'    => 'success',
                                    'partial' => 'warning',
                                    'overdue' => 'danger',
                                    default   => 'gray',
                                }),
                        ])
                        ->columns(6),
                ])
                ->visible(fn (Payment $record) => $record->orderPayments->isNotEmpty()),
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['folio', 'receipt_number'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return "{$record->folio} — " . ($record->student?->full_name ?? '');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view'   => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
