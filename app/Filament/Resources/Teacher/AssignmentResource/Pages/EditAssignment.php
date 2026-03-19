<?php

namespace App\Filament\Resources\Teacher\AssignmentResource\Pages;

use App\Filament\Resources\Teacher\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
