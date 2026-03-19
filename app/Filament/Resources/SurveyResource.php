<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SurveyResource\Pages;
use App\Filament\Resources\SurveyResource\RelationManagers;
use App\Models\Survey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Cuestionarios';
    protected static ?string $navigationLabel = 'Cuestionarios';
    protected static ?string $pluralModelLabel = 'Cuestionarios';
    protected static ?string $modelLabel = 'Cuestionario';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_survey');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Cuestionario')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Cuestionario')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Evaluación')
                            ->required()
                            ->options([
                                'docente' => 'Evaluación Docente',
                                'administrativo' => 'Evaluación Administrativa',
                                'servicio' => 'Evaluación de Servicios',
                                'infraestructura' => 'Evaluación de Infraestructura',
                            ])
                            ->default('docente'),
                        Forms\Components\Toggle::make('is_default')
                            ->label('¿Es cuestionario por defecto?')
                            ->helperText('Solo puede haber un cuestionario por defecto por tipo'),
                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Preguntas')
                    ->schema([
                        Forms\Components\Repeater::make('questions')
                            ->relationship('questions')
                            ->schema([
                                Forms\Components\Textarea::make('question')
                                    ->label('Pregunta')
                                    ->required()
                                    ->rows(2),
                                Forms\Components\Select::make('type')
                                    ->label('Tipo de Respuesta')
                                    ->required()
                                    ->options([
                                        'scale' => 'Escala Numérica',
                                        'text' => 'Texto Libre',
                                        'single_choice' => 'Selección Única',
                                        'multiple_choice' => 'Selección Múltiple',
                                    ])
                                    ->default('scale')
                                    ->live(),
                                Forms\Components\TextInput::make('order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('required')
                                    ->label('Obligatoria')
                                    ->default(true),
                            ])
                            ->orderColumn('order')
                            ->collapsible()
                            ->defaultItems(1),
                    ])
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'docente' => 'success',
                        'administrativo' => 'warning',
                        'servicio' => 'info',
                        'infraestructura' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'docente' => 'Evaluación Docente',
                        'administrativo' => 'Eval. Administrativa',
                        'servicio' => 'Eval. Servicios',
                        'infraestructura' => 'Eval. Infraestructura',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Preguntas')
                    ->counts('questions')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Por Defecto')
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'docente' => 'Evaluación Docente',
                        'administrativo' => 'Evaluación Administrativa',
                        'servicio' => 'Evaluación de Servicios',
                        'infraestructura' => 'Evaluación de Infraestructura',
                    ]),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Por Defecto'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('statistics')
                    ->label('Ver Estadísticas')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalHeading('Seleccionar Ciclo')
                    ->modalDescription('Elige el ciclo del que deseas ver los analíticos.')
                    ->modalSubmitActionLabel('Ver Analíticos')
                    ->form([
                        Forms\Components\Select::make('cycle_id')
                            ->label('Ciclo')
                            ->options(function (Survey $record) {
                                return \App\Models\Cycle::whereHas('surveyRelated', function ($q) use ($record) {
                                    $q->where('survey_id', $record->id);
                                })->orderByDesc('starts_at')->pluck('name', 'id');
                            })
                            ->default(fn () => \App\Models\Cycle::active()->current()->first()?->id)
                            ->required()
                            ->placeholder('Selecciona un ciclo'),
                    ])
                    ->action(function (Survey $record, array $data, \Livewire\Component $livewire): void {
                        $url = route('filament.admin.resources.surveys.statistics', $record) . '?cycle_id=' . $data['cycle_id'];
                        $livewire->redirect($url);
                    }),
                Tables\Actions\Action::make('send_emails')
                    ->label('Enviar Correos')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn (Survey $record): bool => $record->type === 'docente')
                    ->url(fn (Survey $record): string => route('filament.admin.resources.surveys.send-emails', $record)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSurveys::route('/'),
            'create' => Pages\CreateSurvey::route('/create'),
            'edit' => Pages\EditSurvey::route('/{record}/edit'),
            'statistics' => Pages\SurveyStatistics::route('/{record}/statistics'),
            'question-detail' => Pages\QuestionDetail::route('/{record}/question/{question}'),
            'send-emails' => Pages\SendEmails::route('/{record}/send-emails'),
        ];
    }
}
