<?php

namespace App\Filament\Support\ActivitylogResource\Pages;

use App\Filament\Support\ActivitylogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivitylog extends ViewRecord
{
    public static function getResource(): string
    {
        return ActivitylogResource::class;
    }
}
