<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Group;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Livewire\Form;
use Illuminate\Database\Eloquent\Builder;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;
    protected static string $view = 'filament.resources.student-resource.pages.view-student';

    public function getActions(): array
    {
        $actions = [];

        if (! $this->record->last_inscription || $this->record->last_inscription->status !== 'active') {
            $actions[] = Action::make('inscribir')
                ->label('Inscribir estudiante')
                ->modalHeading('Inscribir al estudiante '. $this->record->name)
                ->modalSubheading('Selecciona la carrera y el grupo al que deseas inscribir al estudiante')
                ->icon('heroicon-o-plus')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('career_id')
                        ->label('Carrera')
                        ->options(
                            \App\Models\Career::where('status', 'active')
                                ->get()
                                ->mapWithKeys(fn ($career) => [
                                    $career->id => "{$career->code} - {$career->name}"
                                ])
                        )
                        ->searchable()
                        ->preload()
                        ->afterStateUpdated(fn (callable $set) => $set('group_id', null))
                        ->helperText('Selecciona primero una carrera'),
                    Forms\Components\Select::make('group_id')
                        ->label('Grupo')
                        ->options(function (callable $get){
                            $careerId = $get('career_id');
                            if (!$careerId) return [];
                            return Group::whereHas('period', function (Builder $query) use ($careerId) {
                                $query->where('career_id', $careerId);
                            })->with('period.career')->get()->pluck('name','id');
                        })
                        ->searchable()
                        ->required()
                        ->reactive(),
                ])
                ->action(function (array $data) {
                    \App\Models\Inscription::create([
                        'student_id' => $this->record->id,
                        'group_id'   => $data['group_id'],
                        'status'     => 'active',
                    ]);
                    Notification::make()
                        ->title('Inscripción exitosa')
                        ->body('El estudiante ha sido inscrito correctamente.')
                        ->success()
                        ->send();
                });
        }
        return $actions;
    }

}
