<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentConceptResource\Pages;
use App\Models\PaymentConcept;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentConceptResource extends Resource
{
    protected static ?string $model = PaymentConcept::class;
    protected static ?string $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Conceptos de Pago';
    protected static ?string $modelLabel      = 'Concepto';
    protected static ?string $pluralModelLabel = 'Conceptos de Pago';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_payment::concept');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información')->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')->required()->unique(ignoreRecord: true)->maxLength(50),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')->rows(2),
                Forms\Components\Select::make('type')
                    ->label('Tipo')->required()
                    ->options([
                        'mensualidad'  => 'Mensualidad',
                        'inscripcion'  => 'Inscripción',
                        'constancia'   => 'Constancia',
                        'seguro'       => 'Seguro',
                        'credencial'   => 'Credencial',
                        'practica'     => 'Práctica',
                        'otro'         => 'Otro',
                    ]),
                Forms\Components\TextInput::make('default_amount')
                    ->label('Monto por defecto')->numeric()->prefix('$')->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Periodicidad')->schema([
                Forms\Components\Toggle::make('is_periodic')->label('Es periódico')->live(),
                Forms\Components\Select::make('period_type')->label('Período')
                    ->options([
                        'mensual'    => 'Mensual',
                        'bimestral'  => 'Bimestral',
                        'semestral'  => 'Semestral',
                        'anual'      => 'Anual',
                    ])->visible(fn ($get) => $get('is_periodic')),
            ])->columns(2),

            Forms\Components\Section::make('Impuestos')->schema([
                Forms\Components\Toggle::make('is_taxable')->label('Aplica IVA')->live(),
                Forms\Components\TextInput::make('tax_rate')->label('Tasa IVA (%)')
                    ->numeric()->suffix('%')->default(0)
                    ->visible(fn ($get) => $get('is_taxable')),
                Forms\Components\Toggle::make('active')->label('Activo')->default(true),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\BadgeColumn::make('type')->label('Tipo')
                    ->colors([
                        'primary'  => 'mensualidad',
                        'success'  => 'inscripcion',
                        'info'     => 'constancia',
                        'warning'  => 'seguro',
                        'gray'     => 'credencial',
                        'danger'   => 'practica',
                    ]),
                Tables\Columns\TextColumn::make('default_amount')->label('Monto')->money('MXN')->sortable(),
                Tables\Columns\IconColumn::make('is_periodic')->label('Periódico')->boolean(),
                Tables\Columns\IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Tipo')
                    ->options([
                        'mensualidad' => 'Mensualidad', 'inscripcion' => 'Inscripción',
                        'constancia'  => 'Constancia',  'seguro'      => 'Seguro',
                        'credencial'  => 'Credencial',  'practica'    => 'Práctica',
                    ]),
                Tables\Filters\TernaryFilter::make('active')->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPaymentConcepts::route('/'),
            'create' => Pages\CreatePaymentConcept::route('/create'),
            'edit'   => Pages\EditPaymentConcept::route('/{record}/edit'),
        ];
    }
}
