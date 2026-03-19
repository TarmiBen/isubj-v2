<?php

namespace App\Filament\Resources\MonthlyFeeConfigResource\Pages;

use App\Filament\Resources\MonthlyFeeConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyFeeConfigs extends ListRecords
{
    protected static string $resource = MonthlyFeeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}