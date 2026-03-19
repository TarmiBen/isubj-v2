<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SurveyQuestionResource\Pages;
use App\Filament\Resources\SurveyQuestionResource\RelationManagers;
use App\Models\SurveyQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SurveyQuestionResource extends Resource
{
    protected static ?string $model = SurveyQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Este recurso no debe aparecer en el menú principal
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('survey_id')
                    ->relationship('survey', 'name')
                    ->required(),
                Forms\Components\TextInput::make('question')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\Toggle::make('required')
                    ->required(),
                Forms\Components\TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('survey.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\IconColumn::make('required')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListSurveyQuestions::route('/'),
            'create' => Pages\CreateSurveyQuestion::route('/create'),
            'edit' => Pages\EditSurveyQuestion::route('/{record}/edit'),
        ];
    }
}
