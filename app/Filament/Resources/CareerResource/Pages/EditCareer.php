<?php

namespace App\Filament\Resources\CareerResource\Pages;

use App\Filament\Resources\CareerResource;
use App\Models\Period;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareer extends EditRecord
{
    protected static string $resource = CareerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }


    protected function afterSave(): void
    {
        $career = $this->record;
        $nSubjects = $career->subject->count();
        if ($nSubjects > 0) {
            return;
        }
        $career->periods()->delete();
        $durationName = $career->duration->name ?? '';
        for ($i = 1; $i <= $career->duration_time; $i++) {
            Period::create([
                'name' => "{$i} {$durationName}",
                'number' => $i,
                'career_id' => $career->id,
            ]);
        }
    }
}
