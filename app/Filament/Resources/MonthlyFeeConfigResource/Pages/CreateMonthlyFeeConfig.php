<?php

namespace App\Filament\Resources\MonthlyFeeConfigResource\Pages;

use App\Filament\Resources\MonthlyFeeConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonthlyFeeConfig extends CreateRecord
{
    protected static string $resource = MonthlyFeeConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
