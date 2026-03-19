<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use App\Services\PhotoService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('photo') && $this->record->photo) {
            PhotoService::optimizeOriginal($this->record->photo);
            $thumbPath = PhotoService::generateThumbnail($this->record->photo);
            $this->record->updateQuietly(['photo_thumb' => $thumbPath]);
        }
    }
}
