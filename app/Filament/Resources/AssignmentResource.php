<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Filament\Resources\AssignmentResource\RelationManagers;
use App\Models\Assignment;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;
use App\Models\Subject;



class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Asignaturas';
    protected static ?string $modelLabel = 'Asignaturas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('career_id')
                    ->label('Carrera')
                    ->options(function () {
                        return \App\Models\Career::where('status','active')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->afterStateUpdated(fn (callable $set) => $set('group_id', null))
                    ->helperText('Selecciona primero una carrera'),
                Forms\Components\Select::make('group_id')
                    ->label('Grupo')
                   ->options(function (callable $get){
                       $careerId = $get('career_id');
                       if (!$careerId) return [];
                       return Group::whereHas('period', function (Builder $query) use ($careerId) {
                           $query->where('career_id', $careerId);
                       })->with('period.career')->get()->pluck('name','id');
                   })
                    ->searchable()
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('teacher_id')
                    ->label('Maestro')
                    ->relationship('teacher', 'first_name',  modifyQueryUsing: fn ($query) => $query->where('status', 'active'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name1} {$record->last_name2}")
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('subject_id')
                    ->label('Materia')
                    ->options(function (callable $get) {
                        $groupId = $get('group_id');
                        $group = Group::with('period.career')->find($groupId);
                        if (!$group || !$group->period || !$group->period->career) return [];
                        return Subject::where('career_id', $group->period->career->id)->pluck('name', 'id');
                    })
                    ->required()
                    ->reactive()
                    ->disabled(fn (callable $get) => !$get('group_id'))
                    ->helperText('Selecciona primero un grupo'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group.name')
                    ->Label('Grupo')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.lastname1')
                    ->label('Profesor')
                    ->searchable()
                    ->getStateUsing(fn ($record) => "{$record->teacher->first_name} {$record->teacher->last_name1} {$record->teacher->last_name2}"),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Materia')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery()->orderBy('created_at', 'desc');

        if ($user->hasRole('super_admin')) {
            return $query;
        }
        if ($user->hasRole('profesor')) {
            return $query->where('teacher_id', $user->userable_id);
        }
        return $query->whereRaw('1=0');
    }



}
