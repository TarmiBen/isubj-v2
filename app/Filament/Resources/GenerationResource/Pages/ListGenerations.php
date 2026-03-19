<?php

namespace App\Filament\Resources\GenerationResource\Pages;

use App\Filament\Resources\GenerationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGenerations extends ListRecords
{
    protected static string $resource = GenerationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
