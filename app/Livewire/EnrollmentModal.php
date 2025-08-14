<?php

namespace App\Http\Livewire;

use App\Models\Inscription;
use App\Models\Student;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

class EnrollmentModal extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $student_id = null;
    protected $listeners = ['openEnrollmentModal' => 'openModal'];

    public ?int $group_id = null;
    public string $status = 'activo';
    public bool $isOpen = false;

    public $students = [];
    public $groups = [];




    protected function getFormModel(): ?string
    {
        return Inscription::class;
    }

    public function mount(): void
    {
        $this->students = Student::all()->toArray();
        $this->groups = Group::all()->toArray();

        $this->form->fill([
            'status' => $this->status,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('student_id')
                ->label('Estudiante')
                ->options(collect($this->students)->pluck('first_name', 'id')->toArray())
                ->searchable()
                ->required(),

            Forms\Components\Select::make('group_id')
                ->label('Grupo')
                ->options(collect($this->groups)->pluck('name', 'id')->toArray())
                ->searchable()
                ->required(),

            Forms\Components\Hidden::make('status')->default('activo'),
        ];
    }

    public function openModal()
    {
        $this->reset(['student_id', 'group_id']);
        $this->status = 'activo';
        $this->isOpen = true;
        $this->form->fill();
    }

    public function save()
    {
        $this->form->validate();

        $data = $this->form->getState();

        Inscription::create($data);

        $this->isOpen = false;

        $this->emit('enrollmentCreated');
    }

    public function render()
    {
        return view('livewire.enrollment-modal');
    }
}
