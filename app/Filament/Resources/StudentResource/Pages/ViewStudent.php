<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Support\CommonActions;
use App\Services\PhotoService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;


class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;
    protected static string $view = 'filament.resources.student-resource.pages.view-student';

    protected static ?string $navigationLabel = 'Detalle Estudiante';

    public function getTitle(): string
    {
        return "Detalle del Estudiante: {$this->record->name} {$this->record->last_name1}";
    }

    public function getActions(): array
    {
        $actions = [];

        if (! $this->record->last_inscription || $this->record->last_inscription->status !== 'active') {
            $actions[] = CommonActions::EnrollStudent($this->record);
        }

        $actions[] = CommonActions::uploadDocumentModel($this->record);
        $actions[] = CommonActions::IconReportCardStudent($this->record->id);

        return $actions;
    }

    // ── Acción: subir / cambiar foto ─────────────────────────────────────────

    public function uploadPhotoAction(): Action
    {
        $record = $this->record;

        return Action::make('uploadPhoto')
            ->label($record->photo ? 'Cambiar foto' : 'Subir foto')
            ->icon('heroicon-o-camera')
            ->color('primary')
            ->modalHeading('Foto del alumno')
            ->modalDescription('Sube una imagen cuadrada. Máximo 15 MB. Se comprimirá automáticamente.')
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
                    ->maxSize(15360)                      // 15 MB en KB
                    ->disk('public')
                    ->directory('students/' . $record->id)
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->required(),
            ])
            ->action(function (array $data) use ($record): void {
                $photoPath = is_array($data['photo']) ? reset($data['photo']) : $data['photo'];

                // Optimizar original (square-crop + max 1200px) y generar miniatura
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
            });
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        if (!$bytes) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

