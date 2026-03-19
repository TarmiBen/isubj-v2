<?php

namespace App\Filament\Resources\Teacher;

use App\Filament\Resources\Teacher\TeacherSubjectResource\Pages\ListTeacherSubjects;
use App\Filament\Resources\Teacher\TeacherSubjectResource\Pages\ViewTeacherSubject;
use App\Models\Assignment;
use App\Models\Subject;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeacherSubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Mis Asignaturas';
    protected static ?string $modelLabel = 'Asignatura';
    protected static ?string $pluralModelLabel = 'Mis Asignaturas';
    protected static ?string $navigationGroup = 'Profesor';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return false;
        }
        return $user->userable_type === 'App\\Models\\Teacher';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->disabled(),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->disabled(),
                Forms\Components\TextInput::make('credits')
                    ->label('Créditos')
                    ->disabled(),
                Forms\Components\TextInput::make('career.name')
                    ->label('Carrera')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de la Asignatura')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credits')
                    ->label('Créditos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('career.name')
                    ->label('Carrera')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignments_count')
                    ->label('Grupos Asignados')
                    ->counts('assignments')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('code');
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
            'index' => ListTeacherSubjects::route('/'),
            'view' => ViewTeacherSubject::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (!$user instanceof User || $user->userable_type !== 'App\\Models\\Teacher') {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // No results
        }

        $teacherId = $user->userable_id;

        return parent::getEloquentQuery()
            ->whereHas('assignments', function (Builder $query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->with(['career', 'assignments' => function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            }])
            ->withCount(['assignments' => function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            }]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
