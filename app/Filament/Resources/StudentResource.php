<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('student_number')
                    ->label('Matrícula')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('name')
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
                    ->maxLength(255),
                Forms\Components\Select::make('gender')
                    ->label('Género')
                    ->options([
                        'M' => 'Masculino',
                        'F' => 'Femenino',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('date_of_birth')
                    ->label('Fecha de nacimiento')
                    ->required(),
                Forms\Components\TextInput::make('curp')
                    ->required()
                    ->maxLength(18),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('phone')
                    ->label('Celular')
                    ->tel()
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('street')
                    ->label('Calle')
                    ->required()
                    ->maxLength(100),
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
                Forms\Components\DatePicker::make('enrollment_date')
                    ->label('Fecha de inscripción')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estatus')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'graduate' => 'Graduado',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('guardian_name')
                    ->label('Nombre del tutor')
                    ->maxLength(150),
                Forms\Components\TextInput::make('guardian_phone')
                    ->label('Teléfono del tutor')
                    ->tel()
                    ->maxLength(15),
                Forms\Components\TextInput::make('emergency_contact_name')
                    ->label('Contacto de emergencia')
                    ->maxLength(150),
                Forms\Components\TextInput::make('emergency_contact_phone')
                    ->label('Teléfono de contacto de emergencia')
                    ->tel()
                    ->maxLength(15),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name1')
                    ->label('Apellido Paterno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name2')
                    ->label('Apellido Materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Genero'),
                Tables\Columns\TextColumn::make('Fecha de cumpleaños')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('curp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Calle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Ciudad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Código Postal')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Ciudad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Estatus'),
                Tables\Columns\TextColumn::make('guardian_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guardian_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('emergency_contact_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('emergency_contact_phone')
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
                //
            ])
            ->actions([
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
