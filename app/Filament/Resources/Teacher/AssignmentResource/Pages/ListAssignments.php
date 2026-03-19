<?php

namespace App\Filament\Resources\Teacher\AssignmentResource\Pages;

use App\Filament\Resources\Teacher\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssignments extends ListRecords
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
