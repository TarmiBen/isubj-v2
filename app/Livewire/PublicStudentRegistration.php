<?php

namespace App\Livewire;

use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class PublicStudentRegistration extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono Celular')
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
                Forms\Components\TextInput::make('guardian_name')
                    ->label('Nombre del Tutor')
                    ->maxLength(150),
                Forms\Components\TextInput::make('guardian_phone')
                    ->label('Teléfono del Tutor')
                    ->tel()
                    ->maxLength(15),
                Forms\Components\TextInput::make('emergency_contact_name')
                    ->label('Nombre de Contacto de Emergencia')
                    ->maxLength(150),
                Forms\Components\TextInput::make('emergency_contact_phone')
                    ->label('Teléfono de Contacto de Emergencia')
                    ->tel()
                    ->maxLength(15),
            ])
            ->statePath('data')
            ->model(Student::class);
    }

    public function create()
    {
        $data = $this->form->getState();
        $data['status'] = 'inactive';

        $record = Student::create($data);

        $this->form->model($record)->saveRelationships();
        return redirect()->route('student.create')->with('success', 'Alumno registrado correctamente.');
    }

    public function render(): View
    {
        return view('livewire.public-student-registration');
    }
}
