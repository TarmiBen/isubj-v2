<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use App\Mail\UserPasswordResetMail;
use App\Mail\RandomPasswordMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuarios';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_user');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn ($state) => filled($state))
                    ->hiddenOn('view'),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirmar Contraseña')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(false)
                    ->same('password')
                    ->hiddenOn('view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Actualización')
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
                Tables\Actions\Action::make('resetPassword')
                    ->label('Restablecer Contraseña')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Restablecer Contraseña del Usuario')
                    ->modalDescription('Se enviará un correo electrónico al usuario con un enlace para restablecer su contraseña.')
                    ->modalSubmitActionLabel('Enviar Correo')
                    ->action(function (User $record) {
                        // Generar token de reset
                        $token = Password::createToken($record);

                        try {
                            // Usar el mail genérico para usuarios
                            Mail::to($record->email)->send(new UserPasswordResetMail($record, $token));

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
                Tables\Actions\Action::make('generateRandomPassword')
                    ->label('Generar Contraseña Aleatoria')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generar Nueva Contraseña')
                    ->modalDescription('Se generará una contraseña aleatoria y se enviará por correo electrónico al usuario. También se mostrará aquí para que puedas copiarla.')
                    ->modalSubmitActionLabel('Generar y Enviar')
                    ->action(function (User $record) {
                        // Generar contraseña aleatoria de 12 caracteres
                        $randomPassword = Str::random(12);

                        try {
                            // Actualizar la contraseña del usuario
                            $record->update([
                                'password' => Hash::make($randomPassword)
                            ]);

                            // Enviar correo con la nueva contraseña
                            Mail::to($record->email)->send(new RandomPasswordMail($record, $randomPassword));

                            // Mostrar notificación con la contraseña para copiar
                            Notification::make()
                                ->title('Contraseña Generada Exitosamente')
                                ->body("Nueva contraseña generada: <code style='background:#f3f4f6; padding:4px 8px; border-radius:4px; font-family:monospace; font-size:14px; font-weight:bold;'>{$randomPassword}</code><br><br>✉️ Se ha enviado un correo a {$record->email} con la información.<br><br>💡 Tip: Haz clic en 'Copiar Contraseña' para copiarla al portapapeles.")
                                ->success()
                                ->persistent() // Hacer la notificación persistente
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('copyPassword')
                                        ->label('📋 Copiar Contraseña')
                                        ->button()
                                        ->color('primary')
                                        ->action(function () use ($randomPassword) {
                                            // Esta acción se maneja con JavaScript personalizado
                                            return response()->json([
                                                'password' => $randomPassword,
                                                'message' => 'Contraseña copiada al portapapeles'
                                            ]);
                                        })
                                        ->extraAttributes([
                                            'onclick' => "
                                                const password = '{$randomPassword}';
                                                if (navigator.clipboard) {
                                                    navigator.clipboard.writeText(password).then(() => {
                                                        // Mostrar mensaje de éxito
                                                        const button = this;
                                                        const originalText = button.textContent;
                                                        button.textContent = '✅ ¡Copiada!';
                                                        button.style.backgroundColor = '#10b981';
                                                        setTimeout(() => {
                                                            button.textContent = originalText;
                                                            button.style.backgroundColor = '';
                                                        }, 2000);
                                                    }).catch(() => {
                                                        // Fallback para navegadores que no soportan clipboard
                                                        prompt('Copia manualmente esta contraseña:', password);
                                                    });
                                                } else {
                                                    // Fallback para navegadores antiguos
                                                    prompt('Copia manualmente esta contraseña:', password);
                                                }
                                                return false;
                                            ",
                                        ]),
                                    \Filament\Notifications\Actions\Action::make('showPasswordAgain')
                                        ->label('👁️ Mostrar de Nuevo')
                                        ->button()
                                        ->color('gray')
                                        ->action(function () use ($randomPassword) {
                                            Notification::make()
                                                ->title('Contraseña Generada')
                                                ->body("La contraseña es: <code style='background:#f3f4f6; padding:8px 12px; border-radius:4px; font-family:monospace; font-size:16px; font-weight:bold; display:block; margin:8px 0;'>{$randomPassword}</code>")
                                                ->info()
                                                ->duration(10000)
                                                ->send();
                                        }),
                                ])
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al Generar Contraseña')
                                ->body('No se pudo generar la contraseña. Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('generateRandomPasswordsBulk')
                        ->label('Generar Contraseñas Aleatorias')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Generar Contraseñas Aleatorias')
                        ->modalDescription('Se generarán contraseñas aleatorias para todos los usuarios seleccionados y se enviarán por correo electrónico.')
                        ->modalSubmitActionLabel('Generar para Todos')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $results = [];
                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                try {
                                    // Generar contraseña aleatoria
                                    $randomPassword = Str::random(12);

                                    // Actualizar la contraseña
                                    $record->update([
                                        'password' => Hash::make($randomPassword)
                                    ]);

                                    // Enviar correo
                                    Mail::to($record->email)->send(new RandomPasswordMail($record, $randomPassword));

                                    $results[] = [
                                        'user' => $record->name,
                                        'email' => $record->email,
                                        'password' => $randomPassword,
                                        'status' => 'success'
                                    ];
                                    $successCount++;

                                } catch (\Exception $e) {
                                    $results[] = [
                                        'user' => $record->name,
                                        'email' => $record->email,
                                        'error' => $e->getMessage(),
                                        'status' => 'error'
                                    ];
                                    $errorCount++;
                                }
                            }

                            // Crear mensaje de resumen
                            $summary = "✅ Éxito: {$successCount} usuarios";
                            if ($errorCount > 0) {
                                $summary .= " | ❌ Errores: {$errorCount} usuarios";
                            }

                            // Crear detalle de las contraseñas generadas
                            $detailsHtml = "<div style='margin-top:15px;'>";
                            $detailsHtml .= "<h4>Contraseñas generadas:</h4>";
                            $detailsHtml .= "<div style='max-height:300px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:6px; padding:10px; background:#f9fafb;'>";

                            foreach ($results as $result) {
                                if ($result['status'] === 'success') {
                                    $detailsHtml .= "<div style='margin-bottom:10px; padding:8px; background:white; border-radius:4px; border-left:3px solid #10b981;'>";
                                    $detailsHtml .= "<strong>{$result['user']}</strong> ({$result['email']})<br>";
                                    $detailsHtml .= "<code style='background:#f3f4f6; padding:4px 8px; border-radius:4px; font-family:monospace;'>{$result['password']}</code>";
                                    $detailsHtml .= "</div>";
                                } else {
                                    $detailsHtml .= "<div style='margin-bottom:10px; padding:8px; background:#fef2f2; border-radius:4px; border-left:3px solid #ef4444;'>";
                                    $detailsHtml .= "<strong>{$result['user']}</strong> ({$result['email']})<br>";
                                    $detailsHtml .= "<span style='color:#dc2626; font-size:12px;'>Error: {$result['error']}</span>";
                                    $detailsHtml .= "</div>";
                                }
                            }

                            $detailsHtml .= "</div></div>";

                            Notification::make()
                                ->title('Proceso de Generación Completado')
                                ->body($summary . $detailsHtml)
                                ->success()
                                ->persistent()
                                ->send();
                        }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }
}
