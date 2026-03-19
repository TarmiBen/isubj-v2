<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CycleResource\Pages;
use App\Filament\Resources\CycleResource\RelationManagers;
use App\Models\Cycle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CycleResource extends Resource
{
    protected static ?string $model = Cycle::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Cuestionarios';
    protected static ?string $navigationLabel = 'Ciclos';
    protected static ?string $pluralModelLabel = 'Ciclos';
    protected static ?string $modelLabel = 'Ciclo';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_cycle');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Ciclo')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Ciclo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Ciclo Enero-Junio 2024'),
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ej: 2024-1'),
                        Forms\Components\DatePicker::make('starts_at')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->beforeOrEqual('ends_at'),
                        Forms\Components\DatePicker::make('ends_at')
                            ->label('Fecha de Fin')
                            ->required()
                            ->afterOrEqual('starts_at'),
                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->helperText('Solo puede haber un ciclo activo a la vez')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc');
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
            'index' => Pages\ListCycles::route('/'),
            'create' => Pages\CreateCycle::route('/create'),
            'edit' => Pages\EditCycle::route('/{record}/edit'),
        ];
    }
}
