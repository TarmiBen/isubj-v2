<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Services\PhotoService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
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
