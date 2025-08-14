<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers;
use App\Models\Generation;
use App\Models\Group;
use App\Models\Period;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use App\Models\Career;


class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Grupos';
    protected static ?string $modelLabel = 'Grupos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100),
                Select::make('career_id')
                    ->label('Carrera')
                    ->required()
                    ->reactive()
                    ->options(function () {
                        return Career::where('status', 'active')
                            ->get()
                            ->mapWithKeys(fn ($career) => [
                                $career->id => "{$career->abbreviation} - {$career->name}",
                            ])
                            ->toArray();
                    }),

                Select::make('period_id')
                    ->label('Periodo')
                    ->required()
                    ->reactive()
                    ->options(function (callable $get) {
                        $careerId = $get('career_id');
                        return $careerId
                            ? Period::where('career_id', $careerId)
                                ->get()
                                ->mapWithKeys(fn ($period) => [
                                    $period->id => "{$period->name} - {$period->career->name}",
                                ])
                                ->toArray()
                            : [];
                    }),

                Select::make('generation_id')
                    ->label('Generación')
                    ->required()
                    ->options(function (callable $get) {
                        $periodId = $get('period_id');
                        $period = $periodId ? Period::find($periodId) : null;
                        return $period
                            ? Generation::where('career_id', $period->career_id)
                                ->get()
                                ->mapWithKeys(fn ($generation) => [
                                    $generation->id => "{$generation->number} - {$generation->career->name}",
                                ])
                                ->toArray()
                            : [];
                    }),
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
                Tables\Columns\TextColumn::make('period.name')
                    ->label('Peridodo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period.career.name')
                    ->label('Carrera')
                    ->searchable(),
                Tables\Columns\TextColumn::make('generation.number')
                    ->label('Generación')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación ')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->orderByDesc('created_at');
    }



}
