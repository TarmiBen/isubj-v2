<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Asignaturas';

    protected static ?string $modelLabel = 'Asignatura';

    protected static ?string $pluralModelLabel = 'Asignaturas';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        $activeCycle = \App\Models\Cycle::where('active', true)->first();

        // Filtrar por ciclo activo
        if ($activeCycle) {
            $query->where('cycle_id', $activeCycle->id);
        }

        if ($user->userable_type === 'App\Models\Teacher') {
            $query->where('teacher_id', $user->userable_id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                Tables\Columns\TextColumn::make('subject.career.name')
                    ->label('Carrera')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.career.modality.name')
                    ->label('Modalidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher_id')
                    ->label('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.code')
                    ->label('Grupo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Materia')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Maestro')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->actions([

                Tables\Actions\Action::make('details')
                    ->label('Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->url(fn (Assignment $record): string => Pages\ViewAssignment::getUrl(['record' => $record])),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'view-details' => Pages\ViewAssignmentDetails::route('/{record}/documentos'),
            'view'=> Pages\ViewAssignment::route('/{record}/view'),
        ];
    }
}
