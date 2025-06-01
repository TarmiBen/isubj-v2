<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerResource\Pages;
use App\Filament\Resources\CareerResource\RelationManagers;
use App\Models\Career;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CareerResource extends Resource
{
    protected static ?string $model = Career::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('abbreviation')
                    ->label('Abreviatura')
                    ->maxLength(10),
                Forms\Components\TextInput::make('duration_id')
                    ->label('Duración')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_credits')
                    ->label('Creditos totales')
                    ->numeric(),
                Forms\Components\TextInput::make('modality_id')
                    ->label('Modalidad')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('coordinator_id')
                    ->label('Coordinador')
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'archived' => 'Archivado',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Abreviatura')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration_id')
                    ->label('Duración')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_credits')
                    ->label('Créditos Totales')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modality_id')
                    ->label('Modalidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('coordinator_id')
                    ->label('Coordinador')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => [
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ][$state] ?? $state)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListCareers::route('/'),
            'create' => Pages\CreateCareer::route('/create'),
            'edit' => Pages\EditCareer::route('/{record}/edit'),
        ];
    }
    public static function getNavigationLabel(): string
    {
        return 'Carreras';
    }
    public static function getModelLabel(): string
    {
        return 'Carreras';
    }
}
