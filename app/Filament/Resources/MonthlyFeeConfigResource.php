<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyFeeConfigResource\Pages;
use App\Models\MonthlyFeeConfig;
use App\Models\PaymentConcept;
use App\Models\Generation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MonthlyFeeConfigResource extends Resource
{
    protected static ?string $model = MonthlyFeeConfig::class;
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Config. Mensualidades';
    protected static ?string $modelLabel      = 'Configuración';
    protected static ?string $pluralModelLabel = 'Config. Mensualidades';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 11;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_monthly::fee::config');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Configuración base')->schema([
                Forms\Components\Select::make('payment_concept_id')
                    ->label('Concepto de pago')->required()
                    ->options(PaymentConcept::where('type', 'mensualidad')->active()->pluck('name', 'id'))
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $concept = PaymentConcept::find($state);
                            $set('amount', $concept?->default_amount);
                        }
                    }),
                Forms\Components\Select::make('generation_id')
                    ->label('Generación (null = todas)')
                    ->options(Generation::pluck('number', 'id'))
                    ->searchable()->nullable(),
                Forms\Components\TextInput::make('amount')
                    ->label('Monto mensual')->required()->numeric()->prefix('$'),
            ])->columns(3),

            Forms\Components\Section::make('Generación automática')->schema([
                Forms\Components\TextInput::make('generation_day')
                    ->label('Día de generación (1-28)')
                    ->required()->numeric()->minValue(1)->maxValue(28)->default(1)
                    ->helperText('Día del mes en que el cron genera la mensualidad'),
                Forms\Components\TextInput::make('due_days')
                    ->label('Días para vencer')->required()->numeric()->default(10)
                    ->helperText('Días de plazo desde la generación'),
                Forms\Components\TextInput::make('months_count')
                    ->label('Número de meses del ciclo')->required()->numeric()->default(10),
            ])->columns(3),

            Forms\Components\Section::make('Período de vigencia')->schema([
                Forms\Components\Select::make('start_month')
                    ->label('Mes inicio')->required()
                    ->options([
                        1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',
                        5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',
                        9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre',
                    ]),
                Forms\Components\TextInput::make('start_year')
                    ->label('Año inicio')->required()->numeric()->default(now()->year),
                Forms\Components\Toggle::make('active')->label('Activa')->default(true),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('concept.name')->label('Concepto')->searchable(),
                Tables\Columns\TextColumn::make('generation.name')->label('Generación')->default('Todas'),
                Tables\Columns\TextColumn::make('amount')->label('Monto')->money('MXN'),
                Tables\Columns\TextColumn::make('generation_day')->label('Día gen.'),
                Tables\Columns\TextColumn::make('due_days')->label('Días vcto.'),
                Tables\Columns\TextColumn::make('start_month')->label('Mes inicio')
                    ->formatStateUsing(fn ($state) => [
                        1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',
                        7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic',
                    ][$state] ?? $state),
                Tables\Columns\TextColumn::make('start_year')->label('Año'),
                Tables\Columns\TextColumn::make('months_count')->label('# Meses'),
                Tables\Columns\IconColumn::make('active')->label('Activa')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMonthlyFeeConfigs::route('/'),
            'create' => Pages\CreateMonthlyFeeConfig::route('/create'),
            'edit'   => Pages\EditMonthlyFeeConfig::route('/{record}/edit'),
        ];
    }
}
