<?php

namespace App\Filament\Resources\CareerResource\Pages;

use App\Filament\Resources\CareerResource;
use App\Models\Duration;
use App\Models\Period;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCareer extends CreateRecord
{
    protected static string $resource = CareerResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function aftercreate(){
        $career = $this->record;
        $duration = Duration::find($career->duration_time);
        $type = $career->duration_id == 1 ? 'Cuatrimestre' : ($career->duration_id == 2 ? 'Semestre' : 'Año');
        for ($i = 1; $i<=$career->duration_time; $i++){
            Period::create([
                'name' =>  "{$i}º " . $type,
                'number' => $i,
                'career_id' => $career->id,
            ]);
        }

    }
}
