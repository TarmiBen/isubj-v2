<?php

namespace App\Filament\Resources;

use App\Filament\Exports\TeacherExporter;
use App\Filament\Resources\TeacherResource\Pages;
use App\Filament\Resources\TeacherResource\RelationManagers;
use App\Models\Teacher;
use App\Models\User;
use App\Services\PhotoService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ProfesorPasswordMail;
use App\Mail\TeacherPasswordResetMail;
use Illuminate\Support\Facades\Password;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Profesores';
    protected static ?string $modelLabel = 'Profesores';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_teacher');
    }

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
                    ->imageCropAspectRatio('1:1')
                    ->imageEditorAspectRatios(['1:1'])
                    ->imageResizeTargetWidth(1200)
                    ->imageResizeTargetHeight(1200)
                    ->imageResizeMode('cover')
                    ->imageResizeUpscale(false)
                    ->maxSize(15360)
                    ->disk('public')
                    ->directory(fn ($record) => $record ? 'teachers/' . $record->id : 'teachers/tmp')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('emergency_contact_name')
                    ->label('Contacto de emergencia')
                    ->maxLength(150),
                Forms\Components\TextInput::make('emergency_contact_phone')
                    ->label('Teléfono de contacto de emergencia')
                    ->tel()
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
                Tables\Columns\ImageColumn::make('photo_thumb')
                    ->label('Foto')
                    ->getStateUsing(fn ($record) => $record->photo_thumb ?: $record->photo)
                    ->circular()
                    ->width(44)
                    ->height(44)
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
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exporter(\App\Filament\Exports\TeacherExporter::class)
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('uploadPhoto')
                    ->label('Foto')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->modalHeading('Foto del profesor')
                    ->modalDescription('Sube una imagen cuadrada. Máximo 15 MB.')
                    ->modalSubmitActionLabel('Guardar foto')
                    ->form([
                        Forms\Components\FileUpload::make('photo')
                            ->label('Imagen')
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
                            ->directory(fn () => 'teachers/' . request()->route('record', 'tmp'))
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->required(),
                    ])
                    ->action(function (Teacher $record, array $data): void {
                        $photoPath = is_array($data['photo']) ? reset($data['photo']) : $data['photo'];

                        // Mover al directorio correcto si está en tmp
                        if (str_contains($photoPath, 'teachers/tmp/')) {
                            $newPath = 'teachers/' . $record->id . '/' . basename($photoPath);
                            \Illuminate\Support\Facades\Storage::disk('public')->move($photoPath, $newPath);
                            $photoPath = $newPath;
                        }

                        PhotoService::optimizeOriginal($photoPath);
                        $thumbPath = PhotoService::generateThumbnail($photoPath);

                        $record->update([
                            'photo'       => $photoPath,
                            'photo_thumb' => $thumbPath,
                        ]);

                        Notification::make()
                            ->title('Foto actualizada correctamente')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('resetPassword')
                    ->label('Restablecer Contraseña')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Restablecer Contraseña del Profesor')
                    ->modalDescription('Se enviará un correo electrónico al profesor con un enlace para restablecer su contraseña.')
                    ->modalSubmitActionLabel('Enviar Correo')
                    ->action(function (Teacher $record) {
                        // Buscar el usuario asociado al profesor
                        $user = $record->user;

                        if (!$user) {
                            Notification::make()
                                ->title('Error')
                                ->body('No se encontró una cuenta de usuario asociada a este profesor.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Generar token de reset
                        $token = Password::createToken($user);

                        try {
                            // Enviar correo de reset
                            Mail::to($user->email)->send(new TeacherPasswordResetMail($user, $token));

                            Notification::make()
                                ->title('Correo Enviado')
                                ->body('Se ha enviado un correo electrónico con las instrucciones para restablecer la contraseña.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al Enviar Correo')
                                ->body('No se pudo enviar el correo electrónico. Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ExportAction::make()
                        ->exporter(TeacherExporter::class)
                        ->label('Exportar')
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
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->orderByDesc('created_at');
    }


    public static function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $teacher = static::getModel()::create($data);

        $randomPassword = Str::random(8);

        $user = new User([
            'name' => "{$teacher->first_name} {$teacher->last_name1} {$teacher->last_name2}",
            'email' => $teacher->email,
            'password' => Hash::make($randomPassword),
        ]);

        $user->userable()->associate($teacher);
        $user->save();
        Mail::to($teacher->email)->send(new ProfesorPasswordMail($user, $randomPassword));

        return $teacher;
    }
}
