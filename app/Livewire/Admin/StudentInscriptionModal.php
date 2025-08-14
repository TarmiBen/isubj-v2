<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Models\Student;
use App\Models\Career;
use App\Models\Group;
use App\Models\Inscription;
use Filament\Forms;
use Filament\Forms\Components\Select;

class StudentInscriptionModal extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public ?Student $student = null;
    public $careerId = null;
    public $groupId = null;
    public bool $showModal = false;

    protected function getFormSchema(): array
    {
        return [
            Select::make('careerId')
                ->label('Carrera')
                ->options(Career::all()->pluck('name', 'id')->toArray())
                ->reactive()
                ->required()
                ->afterStateUpdated(fn ($state) => $this->groupId = null),

            Select::make('groupId')
                ->label('Grupo')
                ->options(function () {
                    if (!$this->careerId) {
                        return [];
                    }
                    return Group::where('career_id', $this->careerId)
                        ->pluck('code', 'id')
                        ->toArray();
                })
                ->required(),
        ];
    }

    public function mount(?Student $student = null)
    {
        $this->student = $student;
        $this->form->fill();
    }

    public function submit()
    {
        $data = $this->form->getState();

        // Validación extra (Filament ya valida)
        $this->validate([
            'careerId' => 'required|exists:careers,id',
            'groupId' => 'required|exists:groups,id',
        ]);

        Inscription::create([
            'student_id' => $this->student->id,
            'group_id' => $data['groupId'],
            'status' => 'active',
        ]);

        $this->reset(['careerId', 'groupId', 'showModal']);
        session()->flash('success', 'Inscripción realizada correctamente.');

        $this->emit('inscriptionCreated'); // puedes emitir evento para refrescar tablas u otras acciones
    }

    public function render()
    {
        return view('livewire.admin.student-inscription-modal');
    }
}
