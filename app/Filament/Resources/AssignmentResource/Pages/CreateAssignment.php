<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate()
    {
        $credits = $this->record->subject->credits;
        // create # units by credits
        $units = array_map(
            fn($i) => ['name' => "Unidad {$i}"],
            range(1, $credits)
        );

        $this->record->units()->createMany($units);

    }
}
