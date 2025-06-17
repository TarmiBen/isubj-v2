<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GenerationResource\Pages;
use App\Filament\Resources\GenerationResource\RelationManagers;
use App\Models\Generation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;


class GenerationResource extends Resource
{
    protected static ?string $model = Generation::class;
    protected static ?string $navigationLabel = 'Generaciones';
    protected static ?string $modelLabel = 'Generaciones';


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('career_id')
                    ->label('Carrera')
                    ->relationship('career', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $lastGeneration = \App\Models\Generation::where('career_id', $state)
                            ->orderByDesc('number')
                            ->first();
                        $nextNumber = $lastGeneration ? $lastGeneration->number + 1 : 1;
                        $set('number', $nextNumber);
                        $set('end_date', null);
                    }),
                Forms\Components\TextInput::make('number')
                    ->label('Número de generación')
                    ->readOnly()
                    ->reactive()
                    ->helperText('Se asigna automáticamente según la carrera seleccionada'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Fecha de Inicio')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $careerId = $get('career_id');
                        $career = \App\Models\Career::with('duration')->find($careerId);

                        if ($career && $career->duration_time && $career->duration && $state) {
                            $months = $career->duration_time * $career->duration->months;
                            $endDate = Carbon::parse($state)->addMonths($months);
                            $set('end_date', $endDate->format('Y-m-d'));
                        }
                    }),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Fecha de Fin')
                    ->readOnly()
                    ->helperText('Se calcula automáticamente a partir de la fecha de inicio y la duración de la carrera'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('career.name')
                    ->label('Carrera')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Número de Generación')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de Inicio')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha de Fin')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGenerations::route('/'),
            'create' => Pages\CreateGeneration::route('/create'),
            'edit' => Pages\EditGeneration::route('/{record}/edit'),
        ];
    }
}
