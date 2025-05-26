<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Filament\Resources\TeacherResource\RelationManagers;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('employee_number')
                    ->label('Número de empleado')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('last_name1')
                    ->label('Apellido Paterno')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('last_name2')
                    ->label('Apellido Materno')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('gender')
                    ->label('Género')
                    ->options([
                        'M' => 'Masculino',
                        'F' => 'Femenino',
                        'O' => 'Otro',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('date_of_birth')
                    ->label('Fecha de nacimiento')
                    ->required(),
                Forms\Components\TextInput::make('curp')
                    ->label('CURP')
                    ->required()
                    ->maxLength(18),
                Forms\Components\TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono fijo')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('mobile')
                    ->label('Teléfono móvil')
                    ->required()
                    ->maxLength(20),
                Forms\Components\DatePicker::make('hire_date')
                    ->label('Fecha de contratación')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estatus')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'on_leave' => 'En licencia',
                        'retired' => 'Jubilado',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('street')
                    ->label('Calle')
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('city')
                    ->label('Ciudad')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('state')
                    ->label('Estado')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('postal_code')
                    ->label('Código Postal')
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('country')
                    ->label('País')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('specialization')
                    ->label('Especialización')
                    ->required()
                    ->maxLength(150),
                Forms\Components\FileUpload::make('photo')
                    ->label('Foto')
                    ->image()
                    ->imageEditor()
                    ->required()
                    ->maxSize(1024)
                    ->preserveFilenames()
                    ->directory('students')
                    ->visibility('public')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('emergency_contact_name')
                    ->label('Contacto de emergencia')
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('emergency_contact_phone')
                    ->label('Teléfono de contacto de emergencia')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('meta')
                    ->required()
                    ->hidden(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->width(50)
                    ->height(50)
                    ->disk('public'),
                Tables\Columns\TextColumn::make('employee_number')
                    ->label('Número de empleado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name1')
                    ->label('Apellido Paterno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name2')
                    ->label('Apellido Materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Género'),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label('Fecha de nacimiento')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('curp')
                    ->label('CURP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono fijo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('Teléfono móvil')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hire_date')
                    ->label('Fecha de contratación')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->searchable(),
                Tables\Columns\TextColumn::make('street')
                    ->label('Calle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('Estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('postal_code')
                    ->label('Código Postal')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('País')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('specialization')
                    ->label('Especialización')
                    ->searchable(),
                Tables\Columns\TextColumn::make('emergency_contact_name')
                    ->label('Contacto de emergencia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('emergency_contact_phone')
                    ->label('Teléfono de contacto de emergencia')
                    ->searchable(),
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getNavigationLabel(): string
    {
        return 'Profesores';
    }
    public static function getModelLabel(): string
    {
        return 'Profesores';
    }
}
