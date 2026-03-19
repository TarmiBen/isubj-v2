<?php

namespace App\Filament\Resources\AssignmentResource\Actions;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class ManageRubrosAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'manageRubros')
            ->label('Gestionar Rubros')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->modalHeading('Gestionar Rubros de la Unidad')
            ->modalDescription('Agregue los rubros y sus valores. La suma total debe ser 100.')
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
                            ->integer()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%')
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
            ->action(function (array $data, $record, $livewire = null) {
                $rubros = $data['rubros'] ?? [];
                $aplicarATodas = $data['aplicar_a_todas'] ?? false;

                // Validar que la suma sea 100
                $suma = array_sum(array_column($rubros, 'valor'));

                if ($suma !== 100) {
                    Notification::make()
                        ->title('Error de Validación')
                        ->body("La suma de los valores debe ser 100. Suma actual: {$suma}")
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
                if ($aplicarATodas && $livewire && isset($livewire->record)) {
                    $assignment = $livewire->record;
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
            });
    }
}
