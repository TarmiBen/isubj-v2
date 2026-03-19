<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Generation;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use App\Filament\Exports\StudentExporter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Livewire\Livewire;
use Illuminate\Validation\Rule;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Estudiantes';
    protected static ?string $modelLabel = 'Estudiante';
    protected static ?string $pluralModelLabel = 'Estudiantes';
    protected static ?string $navigationGroup = 'Gestión de Estudiantes';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_student');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('student_number')
                    ->label('Matrícula')
                    ->required()
                    ->rule(fn ($record) => Rule::unique('students', 'student_number')->ignore($record))
                    ->validationMessages([
                        'unique' => 'Esta matrícula ya está registrada para otro estudiante.',
                    ])
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
                    ->maxLength(18)
                    ->rule(fn ($record) => Rule::unique('students', 'curp')->ignore($record))
                    ->validationMessages([
                        'unique' => 'Esta CURP ya está registrada para otro estudiante.',
                    ])
                    ->maxLength(18),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->rule(fn ($record) => Rule::unique('students', 'email')->ignore($record))
                    ->validationMessages([
                        'unique' => 'Este correo ya está registrada por otro estudiante.',
                    ])
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
                    ->label('Fecha de inscripción'),
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
                Forms\Components\Select::make('generation_id')
                    ->label('Generación')
                    ->options(Generation::with('career')->get()->mapWithKeys(fn ($g) => [
                        $g->id => "{$g->number}" . ($g->career ? " - {$g->career->name}" : ''),
                    ]))
                    ->searchable()
                    ->nullable(),
                Forms\Components\FileUpload::make('photo')
                    ->label('Foto')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->imageEditorAspectRatios(['1:1'])
                    ->imageResizeTargetWidth(1200)
                    ->imageResizeTargetHeight(1200)
                    ->imageResizeMode('cover')
                    ->imageResizeUpscale(false)
                    ->maxSize(15360)
                    ->disk('public')
                    ->directory(fn ($record) => $record ? 'students/' . $record->id : 'students/tmp')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),
                Section::make('Documentos Personales')
                    ->schema([
                        FileUpload::make('acta_nacimiento')
                            ->label('Acta de nacimiento')
                            ->disk('public')
                            ->directory('documents')
                            ->preserveFilenames()
                            ->storeFileNamesIn('acta_nacimiento_path')
                            ->maxSize(2048),
                        FileUpload::make('curp_doc')
                            ->label('CURP')
                            ->disk('public')
                            ->directory('documents')
                            ->preserveFilenames()
                            ->storeFileNamesIn('curp_documento_path')
                            ->maxSize(2048),
                        FileUpload::make('ine')
                            ->label('INE')
                            ->disk('public')
                            ->directory('documents')
                            ->storeFileNamesIn('ine_path')
                            ->preserveFilenames()
                            ->maxSize(2048),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\ImageColumn::make('photo_thumb')
                    ->label('Foto')
                    ->getStateUsing(fn ($record) => $record->photo_thumb ?: $record->photo)
                    ->circular()
                    ->width(44)
                    ->height(44)
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
                    ->label('Género'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => [
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ][$state] ?? $state)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()->exporter(StudentExporter::class) ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->actions([
                Tables\Actions\Action::make('Ver')
                    ->icon('heroicon-o-eye')
                    ->label('Ver')
                    ->url(fn ($record) => StudentResource::getUrl('view', ['record' => $record]))
                  ,
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])


            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()->exporter(StudentExporter::class),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'view' => Pages\ViewStudent::route('/{record}/view'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }

}
