<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentOrderResource\Pages;
use App\Models\PaymentOrder;
use App\Models\PaymentConcept;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
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
                    ->default(0)->live(onBlur: true),
                Forms\Components\TextInput::make('discount_amount')
                    ->label('Descuento')->numeric()->prefix('$')
                    ->default(0)->live(onBlur: true),
                Forms\Components\TextInput::make('tax_amount')
                    ->label('IVA')->numeric()->prefix('$')
                    ->default(0)->live(onBlur: true),
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
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student.full_name')->label('Alumno')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('concept.name')->label('Concepto'),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('MXN')->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')->label('Pagado')->money('MXN'),
                Tables\Columns\TextColumn::make('balance')->label('Saldo')->money('MXN')->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('due_date')->label('Vence')->date('d/m/Y')->sortable(),
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
                    ->query(fn (Builder $q) => $q->where('due_date', '<', now())->whereIn('status', ['pending','partial'])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
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
