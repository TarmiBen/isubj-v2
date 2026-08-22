<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configuraciones';
    protected static string $view = 'filament.admin.pages.system-settings';
    protected static ?string $title = 'Configuraciones del Sistema';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'block_teacher_login' => Setting::get('block_teacher_login', false),
            'block_student_login' => Setting::get('block_student_login', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Accesos')
                    ->description('Habilita o deshabilita temporalmente el inicio de sesión por tipo de usuario.')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\Toggle::make('block_teacher_login')
                            ->label('Bloquear login de maestros')
                            ->helperText('Mientras esté activo, los maestros no podrán iniciar sesión en el panel de docentes.')
                            ->onColor('danger')
                            ->offColor('gray'),

                        Forms\Components\Toggle::make('block_student_login')
                            ->label('Bloquear login de alumnos')
                            ->helperText('Mientras esté activo, los alumnos no podrán iniciar sesión en el panel de alumnos.')
                            ->onColor('danger')
                            ->offColor('gray'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('block_teacher_login', $data['block_teacher_login'], [
            'type' => 'boolean',
            'label' => 'Bloquear login de maestros',
            'group' => 'accesos',
        ]);

        Setting::set('block_student_login', $data['block_student_login'], [
            'type' => 'boolean',
            'label' => 'Bloquear login de alumnos',
            'group' => 'accesos',
        ]);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }
}