<?php

namespace App\Filament\Resources\MonthlyFeeConfigResource\Pages;

use App\Filament\Resources\MonthlyFeeConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyFeeConfig extends EditRecord
{
    protected static string $resource = MonthlyFeeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
