<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\TextInput::make('amount_received')
                    ->label('Monto recibido')->required()->numeric()->prefix('$')->live(),
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
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student.full_name')->label('Alumno')->searchable(),
                Tables\Columns\TextColumn::make('method.name')->label('Método'),
                Tables\Columns\TextColumn::make('amount_received')->label('Recibido')->money('MXN')->sortable(),
                Tables\Columns\TextColumn::make('amount_applied')->label('Aplicado')->money('MXN'),
                Tables\Columns\TextColumn::make('change_amount')->label('Cambio')->money('MXN'),
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
                Tables\Columns\TextColumn::make('receivedBy.name')->label('Cajero'),
            ])
            ->defaultSort('payment_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')
                    ->options(['applied'=>'Aplicado','partial'=>'Parcial','pending'=>'Pendiente','cancelled'=>'Cancelado']),
                Tables\Filters\SelectFilter::make('payment_method_id')->label('Método')
                    ->relationship('method', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
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
