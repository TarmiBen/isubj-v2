<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use App\Models\Subject;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Asignaturas';
    protected static ?string $modelLabel = 'Asignaturas';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_assignment');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cycle_id')
                    ->label('Ciclo')
                    ->options(\App\Models\Cycle::all()->pluck('name', 'id'))
                    ->default(fn () => \App\Models\Cycle::where('active', true)->first()?->id)
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('career_id')
                    ->label('Carrera')
                    ->options(
                        \App\Models\Career::all()
                            ->mapWithKeys(fn ($career) => [
                                $career->id => "{$career->code} - {$career->name}"
                            ])
                    )
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set) {
                        $set('period_id', null);
                        $set('group_id', null);
                        $set('subject_id', null);
                    })
                    ->dehydrated(false), // No guardar este campo

                Forms\Components\Select::make('period_id')
                    ->label('Periodo')
                    ->options(function (callable $get) {
                        $careerId = $get('career_id');
                        if (!$careerId) return [];
                        return \App\Models\Period::where('career_id', $careerId)
                            ->pluck('name', 'id');
                    })
                    ->reactive()
                    ->disabled(fn (callable $get) => !$get('career_id'))
                    ->helperText('Selecciona una Carrera primero')
                    ->afterStateUpdated(function (callable $set) {
                        $set('group_id', null);
                        $set('subject_id', null);
                    })
                    ->dehydrated(false), // No guardar este campo

                Forms\Components\Select::make('group_id')
                    ->label('Grupo')
                    ->options(function (callable $get) {
                        $periodId = $get('period_id');
                        if (!$periodId) return [];
                        return Group::where('period_id', $periodId)
                            ->pluck('code', 'id');
                    })
                    ->reactive()
                    ->searchable()
                    ->required()
                    ->disabled(fn (callable $get) => !$get('period_id'))
                    ->helperText('Selecciona un Periodo primero')
                    ->afterStateUpdated(fn (callable $set) => $set('subject_id', null)),

                Forms\Components\Select::make('teacher_id')
                    ->label('Maestro')
                    ->relationship('teacher', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name1} {$record->last_name2}")
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('subject_id')
                    ->label('Materia')
                    ->options(function (callable $get) {
                        $periodId = $get('period_id');
                        if (!$periodId) return [];

                        $period = \App\Models\Period::find($periodId);
                        if (!$period) return [];

                        return Subject::where('period_id', $periodId)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->reactive()
                    ->disabled(fn (callable $get) => !$get('period_id'))
                    ->helperText('Selecciona primero un periodo'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Materia')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.first_name')
                    ->label('Docente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject.career.name')
                    ->label('Carrera')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.career.modality.name')
                    ->label('Modalidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.code')
                    ->label('Grupo')
                    ->searchable()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Assignment $record): string => static::getUrl('view', ['record' => $record])),
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'view' => Pages\ViewAssignment::route('/{record}'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),

        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $activeCycle = \App\Models\Cycle::where('active', true)->first();

        $query = parent::getEloquentQuery()->orderBy('id', 'desc');

        // Filtrar por ciclo activo
        if ($activeCycle) {
            $query->where('cycle_id', $activeCycle->id);
        }

        // Si es super_admin, ve todo
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Si es profesor con relación a teacher, solo ve sus asignaturas
        if ($user->hasRole('profesor') && $user->userable_id) {
            return $query->where('teacher_id', $user->userable_id);
        }

        // Si tiene permisos pero no es profesor, ve todas las asignaturas
        // El control de acceso ya se maneja con shouldRegisterNavigation() y las políticas
        return $query;
    }
}
