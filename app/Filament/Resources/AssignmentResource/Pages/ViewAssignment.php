<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Exports\AssignmentAttendanceExport;
use App\Filament\Exports\UnitGradesExport;
use App\Filament\Resources\AssignmentResource;

use App\Models\Qualification;
use App\Models\Unit;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ViewAssignment extends ViewRecord
{
    protected static string $resource = AssignmentResource::class;
    protected $units;

    protected static string $view = 'filament.resources.assignment-resource.pages.view-assignment';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // Crear unidades automáticamente si no existen
        $unitsCount = $this->record->units()->count();

        if ($unitsCount === 0) {
            $credits = $this->record->subject->credits;

            for ($i = 1; $i <= $credits; $i++) {
                $this->record->units()->create([
                    'name' => "Unidad {$i}",
                    'meta' => []
                ]);
            }
        }

        // Cargar las unidades con la relación
        $this->record->load('units');
        $this->units = $this->record->units;

        // temp only ajust first


    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configureUnitsType')
                ->label('Configurar Tipo de Unidades')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->modalHeading('Configuración de Tipo de Unidades')
                ->modalDescription('Configure si cada unidad es de tipo Práctica o Teórico. Esta configuración afectará el cálculo de calificaciones finales.')
                ->modalWidth('2xl')
                ->fillForm(function () {
                    $unitsConfig = [];
                    foreach ($this->record->units as $unit) {
                        $unitsConfig['unit_' . $unit->id] = $unit->meta['tipo'] ?? 'teorico';
                    }
                    return $unitsConfig;
                })
                ->form(function () {
                    $schema = [];
                    foreach ($this->record->units as $unit) {
                        $schema[] = \Filament\Forms\Components\Radio::make('unit_' . $unit->id)
                            ->label($unit->name)
                            ->options([
                                'teorico' => 'Teórico',
                                'practico' => 'Práctico',
                            ])
                            ->inline()
                            ->required()
                            ->default('teorico');
                    }
                    return $schema;
                })
                ->action(function (array $data) {
                    try {
                        foreach ($this->record->units as $unit) {
                            $tipo = $data['unit_' . $unit->id] ?? 'teorico';
                            $meta = $unit->meta ?? [];
                            $meta['tipo'] = $tipo;
                            $unit->meta = $meta;
                            $unit->save();
                        }

                        // Recalcular calificaciones finales con la nueva configuración
                        \App\Models\FinalGrade::recalculateForAssignment($this->record->id);

                        Notification::make()
                            ->title('Configuración Guardada')
                            ->body('La configuración de tipos de unidades se ha guardado exitosamente y las calificaciones finales han sido recalculadas.')
                            ->success()
                            ->send();

                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al guardar configuración')
                            ->body('Detalle: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('exportAttendance')
                ->label('Plantilla de Asistencia')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportAttendance()),
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash'),
        ];
    }



    public function infolist(Infolist $infolist): Infolist
    {
        $students = $this->getStudents();
        $this->record->students = $students;

        return $infolist
            ->schema([
                Section::make('Información General de la Asignatura')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('subject.name')
                                    ->label('Materia')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->icon('heroicon-o-book-open')
                                    ->color('primary'),

                                TextEntry::make('group.code')
                                    ->label('Grupo')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-user-group')
                                    ->color('success'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('teacher.first_name')
                                    ->label('Profesor')
                                    ->formatStateUsing(fn ($record) =>
                                        "{$record->teacher->first_name} {$record->teacher->last_name1} {$record->teacher->last_name2}"
                                    )
                                    ->icon('heroicon-o-academic-cap')
                                    ->color('warning'),

                                TextEntry::make('group.period.name')
                                    ->label('Periodo')
                                    ->icon('heroicon-o-calendar')
                                    ->color('info'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('group.period.career.name')
                                    ->label('Carrera')
                                    ->icon('heroicon-o-building-library'),

                                TextEntry::make('group.period.career.code')
                                    ->label('Código de Carrera')
                                    ->icon('heroicon-o-hashtag'),
                            ]),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-information-circle'),

                Section::make('Documentos Adjuntos')
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->label('')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nombre del Documento')
                                            ->weight(FontWeight::SemiBold)
                                            ->icon('heroicon-o-document-text')
                                            ->color('primary'),

                                        TextEntry::make('src')
                                            ->label('Archivo')
                                            ->url(fn ($record) => asset('storage/' . $record->src))
                                            ->openUrlInNewTab()
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('success')
                                            ->formatStateUsing(fn () => 'Descargar'),

                                        TextEntry::make('created_at')
                                            ->label('Fecha de Carga')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-clock'),
                                    ]),
                            ])
                            ->contained(false)
                            ->columnSpanFull()
                            ->hidden(fn ($record) => $record->documents->isEmpty()),

                        Group::make()
                            ->schema([
                                TextEntry::make('no_documents')
                                    ->label('')
                                    ->default('No hay documentos adjuntos a esta asignatura')
                                    ->color('gray')
                                    ->icon('heroicon-o-folder-open'),
                            ])
                            ->hidden(fn ($record) => $record->documents->isNotEmpty()),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-paper-clip')
                    ->description('Documentos relacionados con esta asignatura'),

                Section::make('Unidades de la Asignatura')
                    ->schema([
                        RepeatableEntry::make('units')
                            ->label('')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nombre de la Unidad')
                                            ->weight(FontWeight::Bold)
                                            ->icon('heroicon-o-bookmark')
                                            ->color('primary'),

                                        TextEntry::make('manage_rubros')
                                            ->label('Acciones')
                                            ->default('')
                                            ->suffixActions([
                                                InfolistAction::make('manageRubros')
                                                    ->label('Gestionar Rubros')
                                                    ->icon('heroicon-o-cog-6-tooth')
                                                    ->color('success')
                                                    ->modalHeading('Gestionar Rubros de la Unidad')
                                                    ->modalDescription('Agregue los rubros y sus valores. La suma total debe ser 10.')
                                                    ->modalWidth('lg')
                                                    ->fillForm(function ($record) {
                                                        return [
                                                            'rubros' => $record->meta['rubros'] ?? [],
                                                            'aplicar_a_todas' => false,
                                                        ];
                                                    })
                                                    ->form([
                                                        Repeater::make('rubros')
                                                            ->label('Rubros')
                                                            ->schema([
                                                                TextInput::make('nombre')
                                                                    ->label('Nombre del Rubro')
                                                                    ->required()
                                                                    ->maxLength(255)
                                                                    ->columnSpan(2),

                                                                TextInput::make('valor')
                                                                    ->label('Valor del Rubro')
                                                                    ->required()
                                                                    ->numeric()
                                                                    ->step(0.1)
                                                                    ->minValue(0.1)
                                                                    ->maxValue(10)
                                                                    ->suffix('Pts.')
                                                                    ->columnSpan(1),
                                                            ])
                                                            ->columns(3)
                                                            ->addActionLabel('Agregar Nuevo Rubro')
                                                            ->defaultItems(0)
                                                            ->collapsible()
                                                            ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null)
                                                            ->columnSpanFull()
                                                            ->minItems(1),

                                                        Checkbox::make('aplicar_a_todas')
                                                            ->label('Aplicar esta configuración a las demás unidades de la asignatura')
                                                            ->helperText('Si está marcado, se aplicará la misma configuración de rubros a todas las unidades de esta asignatura.')
                                                            ->default(false)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->action(function (array $data, $record) {
                                                        $rubros = $data['rubros'] ?? [];
                                                        $aplicarATodas = $data['aplicar_a_todas'] ?? false;

                                                        // Validar que la suma sea 10
                                                        $suma = array_sum(array_column($rubros, 'valor'));

                                                        if ($suma != 10) {
                                                            Notification::make()
                                                                ->title('Error de Validación')
                                                                ->body("La suma de los valores debe ser 10. Suma actual: {$suma}")
                                                                ->danger()
                                                                ->send();

                                                            return;
                                                        }

                                                        // Guardar en el campo meta de la unidad actual
                                                        $meta = $record->meta ?? [];
                                                        $meta['rubros'] = $rubros;
                                                        $record->meta = $meta;
                                                        $record->save();

                                                        // Si está marcado "aplicar a todas", aplicar a las demás unidades
                                                        if ($aplicarATodas) {
                                                            $assignment = $this->record;
                                                            $todasLasUnidades = $assignment->units()->where('id', '!=', $record->id)->get();

                                                            $unidadesActualizadas = 0;
                                                            foreach ($todasLasUnidades as $unidad) {
                                                                $metaUnidad = $unidad->meta ?? [];
                                                                $metaUnidad['rubros'] = $rubros;
                                                                $unidad->meta = $metaUnidad;
                                                                $unidad->save();
                                                                $unidadesActualizadas++;
                                                            }

                                                            Notification::make()
                                                                ->title('Rubros Guardados')
                                                                ->body("Los rubros se han aplicado exitosamente a esta unidad y a {$unidadesActualizadas} unidades adicionales.")
                                                                ->success()
                                                                ->send();
                                                        } else {
                                                            Notification::make()
                                                                ->title('Rubros Guardados')
                                                                ->body('Los rubros se han guardado exitosamente en esta unidad.')
                                                                ->success()
                                                                ->send();
                                                        }
                                                    }),

                                                InfolistAction::make('downloadUnitFormat')
                                                    ->label('Descargar Formato')
                                                    ->icon('heroicon-o-document-arrow-down')
                                                    ->color('primary')
                                                    ->action(function ($record) {
                                                        return $this->downloadUnitGrades($record);
                                                    })
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Descargar Formato de Calificaciones')
                                                    ->modalDescription('Se generará un archivo Excel con el formato de calificaciones para esta unidad.')
                                                    ->modalSubmitActionLabel('Descargar'),
                                                // create modal upload  to data

                                                // ... NUEVAS ACCIONES: subir Excel y descargar documento si ya existe ...
                                                InfolistAction::make('uploadUnitGrades')
                                                    ->label('Cargar Calificaciones')
                                                    ->icon('heroicon-o-arrow-up-tray')
                                                    ->color('secondary')
                                                    ->modalHeading('Cargar Calificaciones desde Excel')
                                                    ->modalDescription('Suba su documento con las calificaciones para esta unidad, una vez cargado el documento no podra modificar las calificaciónes')
                                                    ->modalWidth('lg')
                                                    ->form([
                                                        FileUpload::make('excel')
                                                            ->label('Archivo Excel')
                                                            ->required()
                                                            ->directory('units')
                                                            ->disk('public'),
                                                    ])
                                                    ->hidden(fn ($record) => !empty($record->document_src))
                                                    ->action(function (array $data, $record) {
                                                        // $data['excel'] es la ruta relativa en storage/app/public (p.ej. "units/archivo.xlsx")
                                                        $filePath = $data['excel'] ?? null;

                                                        if (!$filePath) {
                                                            Notification::make()
                                                                ->title('Error')
                                                                ->body('No se ha subido el archivo.')
                                                                ->danger()
                                                                ->send();
                                                            return;
                                                        }

                                                        $fullPath = storage_path('app/public/' . $filePath);

                                                        try {
                                                            DB::transaction(function () use ($filePath, $fullPath, $record) {
                                                                $sheets = Excel::toArray(null, $fullPath);
                                                                $sheet = $sheets[0] ?? [];

                                                                // E9 -> fila 9 (índice 8), columna D (índice 3)
                                                                $cellE9 = $sheet[8][3] ?? null;

                                                                if ((string)trim((string)$cellE9) !== (string)$record->id) {
                                                                    Storage::disk('public')->delete($filePath);
                                                                    throw new \Exception("El archivo no coincide con el ID de la unidad ({$record->id}).");
                                                                }

                                                                // Cargar con PhpSpreadsheet para obtener valores calculados de las fórmulas
                                                                $spreadsheet = IOFactory::load($fullPath);
                                                                $worksheet = $spreadsheet->getActiveSheet();

                                                                // Validación previa: verificar que todas las filas con student_id tengan score
                                                                $startRow = 11;
                                                                $filasConError = [];

                                                                for ($i = $startRow; $i < count($sheet); $i++) {
                                                                    $row = $sheet[$i] ?? [];
                                                                    $studentId = $row[0] ?? null; // columna A -> índice 0

                                                                    if (empty($studentId)) {
                                                                        break; // Ya no hay más estudiantes
                                                                    }

                                                                    // Usar PhpSpreadsheet para obtener el valor calculado de la fórmula en AK
                                                                    $rowNumber = $i + 1; // +1 porque $i es índice de array (base 0)
                                                                    $score = $worksheet->getCell('AK' . $rowNumber)->getCalculatedValue();

                                                                    if ( !is_numeric($score) || $score < 0 || $score > 10) {
                                                                        $filasConError[] = $i + 1; // +1 para mostrar número de fila real en Excel
                                                                    }
                                                                }

                                                                // Si hay errores, lanzar excepción
                                                                if (!empty($filasConError)) {
                                                                    $filasTexto = implode(', ', $filasConError);
                                                                    throw new \Exception("Las siguientes filas tienen ID de alumno pero no tienen calificación válida: {$filasTexto}.");
                                                                }

                                                                // Si llegamos aquí, todas las validaciones pasaron
                                                                $record->document_src = $filePath;
                                                                $record->save();

                                                                $teacherId = $record->assignment->teacher_id;

                                                                // Procesar las calificaciones
                                                                for ($i = $startRow; $i < count($sheet); $i++) {
                                                                    $row = $sheet[$i] ?? [];
                                                                    $studentId = $row[0] ?? null; // columna A -> índice 0

                                                                    if (empty($studentId)) {
                                                                        break;
                                                                    }

                                                                    // Usar PhpSpreadsheet para obtener el valor calculado de la fórmula en AK
                                                                    $rowNumber = $i + 1; // +1 porque $i es índice de array (base 0)
                                                                    $score = $worksheet->getCell('AK' . $rowNumber)->getCalculatedValue();
                                                                    // if score is 0 nex student
                                                                    if ($score === 0) {
                                                                        continue;
                                                                    }

                                                                    // Insertar o actualizar en tabla de calificaciones 'grades'
                                                                    Qualification::updateOrInsert(
                                                                        [
                                                                            'teacher_id' => $teacherId,
                                                                            'student_id' => $studentId,
                                                                            'unity_id' => $record->id,
                                                                        ],
                                                                        [
                                                                            'score' => $score,
                                                                            'created_at' => now(),
                                                                            'updated_at' => now(),
                                                                        ]
                                                                    );
                                                                }
                                                            });

                                                            Notification::make()
                                                                ->title('Carga Completa')
                                                                ->body('El archivo fue validado y las calificaciones fueron procesadas exitosamente. Actualice laa Pagina para ver cambios')
                                                                ->success()
                                                                ->send();

                                                            // Recargar la página para actualizar la tabla de calificaciones
                                                            return redirect()->back();

                                                        } catch (\Throwable $e) {
                                                            // eliminar archivo en caso de error
                                                            Storage::disk('public')->delete($filePath);

                                                            Notification::make()
                                                                ->title('Error al procesar el archivo')
                                                                ->body('Detalle: ' . $e->getMessage())
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    }),

                                                InfolistAction::make('downloadUnitDocument')
                                                    ->label('Descargar Documento de Unidad')
                                                    ->icon('heroicon-o-arrow-down-tray')
                                                    ->color('success')
                                                    ->url(fn ($record) => !empty($record->document_src) ? asset('storage/' . $record->document_src) : null)
                                                    ->openUrlInNewTab()
                                                    ->hidden(fn ($record) => empty($record->document_src)),

                                                InfolistAction::make('removeUnitDocument')
                                                    ->label('Eliminar Documento y Calificaciones')
                                                    ->icon('heroicon-o-trash')
                                                    ->color('danger')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Eliminar Documento de Unidad')
                                                    ->modalDescription('¿Está seguro de realizar esta acción? Esto eliminará el documento cargado y borrará todas las calificaciones de esta unidad. Esta acción no se puede deshacer.')
                                                    ->modalSubmitActionLabel('Sí, eliminar')
                                                    ->modalCancelActionLabel('Cancelar')
                                                    ->hidden(fn ($record) => empty($record->document_src))
                                                    ->action(function ($record) {
                                                        try {
                                                            DB::transaction(function () use ($record) {
                                                                // Eliminar el archivo físico si existe
                                                                if (!empty($record->document_src)) {
                                                                    Storage::disk('public')->delete($record->document_src);
                                                                }

                                                                // Limpiar el campo document_src
                                                                $record->document_src = null;
                                                                $record->save();

                                                                // Eliminar todas las calificaciones de esta unidad
                                                                Qualification::where('unity_id', $record->id)->delete();
                                                            });

                                                            Notification::make()
                                                                ->title('Documento y Calificaciones Eliminados')
                                                                ->body('El documento ha sido eliminado y todas las calificaciones de esta unidad han sido borradas exitosamente. Actualice laa Pagina para ver cambios')
                                                                ->success()
                                                                ->send();

                                                            // Recargar la página actual con el ID específico
                                                            return redirect()->to(static::$resource::getUrl('view', ['record' => $this->record->id]));

                                                        } catch (\Throwable $e) {
                                                            Notification::make()
                                                                ->title('Error al eliminar')
                                                                ->body('Detalle: ' . $e->getMessage())
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    }),
                                            ])
                                    ]),
                            ])
                            ->contained(false)
                            ->columnSpanFull()
                            ->hidden(fn ($record) => $record->units->isEmpty()),

                        Group::make()
                            ->schema([
                                TextEntry::make('no_units')
                                    ->label('')
                                    ->default('No hay unidades definidas para esta asignatura')
                                    ->color('gray')
                                    ->icon('heroicon-o-bookmark-slash'),
                            ])
                            ->hidden(fn ($record) => $record->units->isNotEmpty()),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-bookmark-square')
                    ->description('Unidades curriculares y gestión de rubros de evaluación'),

                Section::make('Lista de Estudiantes')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('students_table')
                                    ->label('')
                                    ->view('filament.resources.assignment-resource.components.students-grades-livewire', [
                                        'students' => $students,
                                        'units' => $this->units,
                                        'assignment' => $this->record
                                    ])
                            ])
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-users')
                    ->description('Estudiantes matriculados y sus calificaciones'),
              ]);
    }

    public function getStudents()
    {
        return $this->record->group->inscriptions()
            ->with(['student' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('last_name1')
                    ->orderBy('last_name2')
                    ->orderBy('name')
                    ->select('id', 'name', 'last_name1', 'last_name2');
            }])
            ->get()
            ->pluck('student');


    }

    public function exportAttendance()
    {
        $fileName = 'asistencia_' . $this->record->subject->name . '_' . $this->record->group->code . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new AssignmentAttendanceExport($this->record),
            $fileName
        );
    }

    public function downloadUnitGrades($unit)
    {
        $fileName = 'Calificaciones_' .
                   str_replace(' ', '_', $unit->name) . '_' .
                   str_replace(' ', '_', $this->record->subject->name) . '_' .
                   str_replace(' ', '_', $this->record->group->code) . '_' .
                   now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new UnitGradesExport($unit),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

}
