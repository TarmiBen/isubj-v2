<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;
    protected static ?string $navigationIcon  = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Descuentos';
    protected static ?string $modelLabel      = 'Descuento';
    protected static ?string $pluralModelLabel = 'Descuentos';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 12;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_discount');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificación')->schema([
                Forms\Components\TextInput::make('code')->label('Código')->required()->unique(ignoreRecord: true)->maxLength(50),
                Forms\Components\TextInput::make('name')->label('Nombre')->required(),
                Forms\Components\Textarea::make('description')->label('Descripción')->rows(2),
            ])->columns(2),

            Forms\Components\Section::make('Valor')->schema([
                Forms\Components\Select::make('value_type')->label('Tipo de valor')->required()
                    ->options(['percentage' => 'Porcentaje (%)', 'fixed' => 'Monto fijo ($)'])->live(),
                Forms\Components\TextInput::make('value')->label('Valor')->required()->numeric()
                    ->suffix(fn ($get) => $get('value_type') === 'percentage' ? '%' : '$'),
                Forms\Components\Select::make('applies_to_type')->label('Aplica a')
                    ->options([
                        ''            => 'Todos los conceptos',
                        'mensualidad' => 'Mensualidades',
                        'inscripcion' => 'Inscripciones',
                        'constancia'  => 'Constancias',
                        'seguro'      => 'Seguros',
                        'credencial'  => 'Credenciales',
                        'practica'    => 'Prácticas',
                    ])->nullable(),
            ])->columns(3),

            Forms\Components\Section::make('Condiciones')->schema([
                Forms\Components\Select::make('condition_type')->label('Condición')->required()
                    ->options([
                        'manual'        => 'Manual',
                        'referral'      => 'Referido',
                        'scholarship'   => 'Beca',
                        'early_payment' => 'Pronto pago',
                        'promo'         => 'Promoción',
                    ]),
                Forms\Components\Toggle::make('is_automatic')->label('Automático'),
                Forms\Components\Toggle::make('is_stackable')->label('Acumulable'),
                Forms\Components\Toggle::make('is_recurring')->label('Recurrente'),
            ])->columns(4),

            Forms\Components\Section::make('Vigencia y límites')->schema([
                Forms\Components\DatePicker::make('valid_from')->label('Válido desde'),
                Forms\Components\DatePicker::make('valid_until')->label('Válido hasta'),
                Forms\Components\TextInput::make('max_uses')->label('Máx. usos')->numeric()->nullable(),
                Forms\Components\Toggle::make('active')->label('Activo')->default(true),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\BadgeColumn::make('condition_type')->label('Condición')
                    ->colors([
                        'success' => 'referral',
                        'primary' => 'scholarship',
                        'warning' => 'early_payment',
                        'info'    => 'promo',
                        'gray'    => 'manual',
                    ]),
                Tables\Columns\TextColumn::make('value')->label('Valor')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->value_type === 'percentage' ? "{$state}%" : "\${$state}"
                    ),
                Tables\Columns\TextColumn::make('used_count')->label('Usos'),
                Tables\Columns\IconColumn::make('is_automatic')->label('Auto')->boolean(),
                Tables\Columns\IconColumn::make('is_recurring')->label('Recurrente')->boolean(),
                Tables\Columns\IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('condition_type')->label('Condición')
                    ->options(['manual'=>'Manual','referral'=>'Referido','scholarship'=>'Beca','early_payment'=>'Pronto pago','promo'=>'Promo']),
                Tables\Filters\TernaryFilter::make('active')->label('Activo'),
                Tables\Filters\TernaryFilter::make('is_automatic')->label('Automático'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit'   => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
