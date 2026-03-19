<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Filament\Resources\SubjectResource\RelationManagers;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Materias';
    protected static ?string $modelLabel = 'Materias';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_subject');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->autofocus()
                    ->maxLength(20),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('career_id')
                    ->label('Carrera')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                    ->relationship('career', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('period_id', null)),
                Forms\Components\Select::make('period_id')
                    ->label('Periodo')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}")
                    ->relationship('period', 'name', fn (Builder $query, callable $get) =>
                        $query->where('career_id', $get('career_id'))
                    )
                    ->required()
                    ->disabled(fn (callable $get) => !$get('career_id'))
                    ->helperText('Selecciona una Carrera primero'),
                Forms\Components\TextInput::make('credits')
                    ->label('Creditos')
                    ->required()
                    ->numeric(),
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
                Tables\Columns\TextColumn::make('career.name')
                ->label('Carrera'),
                Tables\Columns\TextColumn::make('period.name')
                ->label('Periodo'),
               // status
                Tables\Columns\TextColumn::make('status')
            ->label('Estado')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    '1' => 'success',
                    '0' => 'danger',
                })
            ->formatStateUsing(fn (string $state): string => match ($state) {
                '1' => 'Activo',
                '0' => 'Inactivo'
            }),
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
                Tables\Actions\DeleteAction::make(),

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
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $activeCycle = \App\Models\Cycle::where('active', true)->first();

        $query = parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');

        // Filtrar materias por asignaciones del ciclo activo
       /* if ($activeCycle) {
            $query->whereHas('assignments', function($q) use ($activeCycle) {
                $q->where('cycle_id', $activeCycle->id);
            });
        }*/

        return $query;
    }
}
