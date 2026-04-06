<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use App\Models\Cycle;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class ViewSubject extends ViewRecord
{
    protected static string $resource = SubjectResource::class;

    public $selectedCycleId;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // Establecer el ciclo activo por defecto
        $activeCycle = Cycle::where('active', true)->first();
        $this->selectedCycleId = $activeCycle?->id;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Detalle de Materia: ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadHelpDocument')
                ->label('Cargar Material de Ayuda')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Cargar Material de Ayuda')
                ->modalDescription('Suba documentos de ayuda para esta materia')
                ->modalWidth('lg')
                ->form([
                    TextInput::make('name')
                        ->label('Nombre del Documento')
                        ->required()
                        ->maxLength(255),
                    FileUpload::make('file')
                        ->label('Archivo')
                        ->required()
                        ->directory('help-materials/subjects')
                        ->disk('public')
                        ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(10240), // 10MB
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->documents()->create([
                            'name' => $data['name'],
                            'src' => $data['file'],
                            'meta' => [
                                'uploaded_by' => auth()->id(),
                                'uploaded_at' => now()->toDateTimeString(),
                            ],
                        ]);

                        Notification::make()
                            ->title('Documento Cargado')
                            ->body('El material de ayuda se ha cargado exitosamente.')
                            ->success()
                            ->send();

                        return redirect()->back();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al cargar documento')
                            ->body('Detalle: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make()
                ->label('Editar'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Obtener ciclos que tienen asignaturas de esta materia
        $cyclesWithAssignments = Cycle::whereHas('assignments', function ($query) {
            $query->where('subject_id', $this->record->id);
        })->orderBy('active', 'desc')->orderBy('created_at', 'desc')->get();

        // Obtener asignaturas del ciclo seleccionado
        $assignments = $this->record->assignments()
            ->when($this->selectedCycleId, function ($query) {
                $query->where('cycle_id', $this->selectedCycleId);
            })
            ->with(['teacher', 'group', 'cycle'])
            ->get();

        return $infolist
            ->schema([
                Section::make('Información de la Materia')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Código')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->icon('heroicon-o-hashtag')
                                    ->color('primary'),

                                TextEntry::make('name')
                                    ->label('Nombre de la Materia')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->icon('heroicon-o-book-open')
                                    ->color('success'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('career.code')
                                    ->label('Código de Carrera')
                                    ->icon('heroicon-o-building-library')
                                    ->color('info'),

                                TextEntry::make('career.name')
                                    ->label('Carrera')
                                    ->icon('heroicon-o-academic-cap')
                                    ->color('warning'),

                                TextEntry::make('period.name')
                                    ->label('Periodo')
                                    ->icon('heroicon-o-calendar')
                                    ->color('secondary'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('credits')
                                    ->label('Créditos')
                                    ->icon('heroicon-o-star')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        '1' => 'success',
                                        '0' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        '1' => 'Activo',
                                        '0' => 'Inactivo',
                                        default => 'N/A',
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Fecha de Creación')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-information-circle'),

                Section::make('Material de Ayuda')
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nombre del Documento')
                                            ->weight(FontWeight::SemiBold)
                                            ->icon('heroicon-o-document-text')
                                            ->color('primary')
                                            ->columnSpan(2),

                                        TextEntry::make('created_at')
                                            ->label('Fecha de Carga')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-clock'),

                                        TextEntry::make('actions')
                                            ->label('Acciones')
                                            ->default('')
                                            ->suffixActions([
                                                InfolistAction::make('download')
                                                    ->label('Descargar')
                                                    ->icon('heroicon-o-arrow-down-tray')
                                                    ->color('success')
                                                    ->url(fn ($record) => asset('storage/' . $record->src))
                                                    ->openUrlInNewTab(),

                                                InfolistAction::make('delete')
                                                    ->label('Eliminar')
                                                    ->icon('heroicon-o-trash')
                                                    ->color('danger')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Eliminar Documento')
                                                    ->modalDescription('¿Está seguro de eliminar este documento? Esta acción no se puede deshacer.')
                                                    ->action(function ($record) {
                                                        try {
                                                            // Eliminar archivo físico
                                                            Storage::disk('public')->delete($record->src);

                                                            // Eliminar registro
                                                            $record->delete();

                                                            Notification::make()
                                                                ->title('Documento Eliminado')
                                                                ->body('El documento ha sido eliminado exitosamente.')
                                                                ->success()
                                                                ->send();

                                                            return redirect()->back();
                                                        } catch (\Throwable $e) {
                                                            Notification::make()
                                                                ->title('Error al eliminar')
                                                                ->body('Detalle: ' . $e->getMessage())
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    }),
                                            ]),
                                    ]),
                            ])
                            ->contained(false)
                            ->columnSpanFull()
                            ->hidden(fn ($record) => $record->documents->isEmpty()),

                        Group::make()
                            ->schema([
                                TextEntry::make('no_documents')
                                    ->label('')
                                    ->default('No hay material de ayuda disponible para esta materia')
                                    ->color('gray')
                                    ->icon('heroicon-o-folder-open'),
                            ])
                            ->hidden(fn ($record) => $record->documents->isNotEmpty()),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-document-duplicate')
                    ->description('Material de ayuda y documentos de referencia para esta materia'),

                Section::make('Asignaturas de la Materia')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('cycle_selector')
                                    ->label('Filtrar por Ciclo')
                                    ->default(function () use ($cyclesWithAssignments) {
                                        if ($cyclesWithAssignments->isEmpty()) {
                                            return 'No hay ciclos con asignaturas';
                                        }
                                        return '';
                                    })
                                    ->hidden(fn () => $cyclesWithAssignments->isNotEmpty())
                                    ->color('gray')
                                    ->icon('heroicon-o-exclamation-triangle'),
                            ])
                            ->hidden(fn () => $cyclesWithAssignments->isNotEmpty()),

                        Grid::make(1)
                            ->schema([
                                TextEntry::make('cycle_info')
                                    ->label('Ciclo Seleccionado')
                                    ->default(function () use ($cyclesWithAssignments) {
                                        $cycle = $cyclesWithAssignments->firstWhere('id', $this->selectedCycleId);
                                        return $cycle ? $cycle->name . ($cycle->active ? ' (Activo)' : '') : 'Ninguno';
                                    })
                                    ->badge()
                                    ->color(function () use ($cyclesWithAssignments) {
                                        $cycle = $cyclesWithAssignments->firstWhere('id', $this->selectedCycleId);
                                        return $cycle && $cycle->active ? 'success' : 'info';
                                    })
                                    ->icon('heroicon-o-calendar'),
                            ])
                            ->hidden(fn () => $cyclesWithAssignments->isEmpty()),

                        TextEntry::make('assignments_list')
                            ->label('')
                            ->state(function () use ($assignments) {
                                if ($assignments->isEmpty()) {
                                    return null;
                                }

                                return $assignments->map(function ($assignment) {
                                    $grupo = $assignment->group->code ?? 'N/A';
                                    $profesor = trim(
                                        ($assignment->teacher->first_name ?? '') . ' ' .
                                        ($assignment->teacher->last_name1 ?? '') . ' ' .
                                        ($assignment->teacher->last_name2 ?? '')
                                    );

                                    $url = route('filament.admin.resources.assignments.view', ['record' => $assignment->id]);

                                    return "Grupo: {$grupo} | Profesor: {$profesor} | <a href='{$url}' target='_blank' class='text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 inline-flex items-center gap-1'><svg class='w-4 h-4 inline' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z' /><path stroke-linecap='round' stroke-linejoin='round' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' /></svg>Ver Detalle</a>";
                                })->toArray();
                            })
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->html()
                            ->columnSpanFull()
                            ->hidden(fn () => $assignments->count() === 0),

                        Group::make()
                            ->schema([
                                TextEntry::make('no_assignments')
                                    ->label('')
                                    ->default('No hay asignaturas para el ciclo seleccionado')
                                    ->color('gray')
                                    ->icon('heroicon-o-bookmark-slash'),
                            ])
                            ->hidden(fn () => $assignments->count() > 0 || $cyclesWithAssignments->isEmpty()),
                    ])
                    ->headerActions([
                        InfolistAction::make('selectCycle')
                            ->label('Cambiar Ciclo')
                            ->icon('heroicon-o-arrows-right-left')
                            ->color('primary')
                            ->form([
                                \Filament\Forms\Components\Select::make('cycle_id')
                                    ->label('Seleccionar Ciclo')
                                    ->options($cyclesWithAssignments->pluck('name', 'id'))
                                    ->default($this->selectedCycleId)
                                    ->required()
                                    ->helperText('Seleccione el ciclo del cual desea ver las asignaturas'),
                            ])
                            ->action(function (array $data) {
                                $this->selectedCycleId = $data['cycle_id'];

                                Notification::make()
                                    ->title('Ciclo Actualizado')
                                    ->body('La vista se ha actualizado con las asignaturas del ciclo seleccionado.')
                                    ->success()
                                    ->send();

                                return redirect()->back();
                            })
                            ->hidden(fn () => $cyclesWithAssignments->isEmpty()),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-bookmark-square')
                    ->description('Asignaturas donde se imparte esta materia'),
            ]);
    }
}

