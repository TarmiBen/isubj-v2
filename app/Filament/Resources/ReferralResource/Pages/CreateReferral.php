<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use App\Filament\Resources\ReferralResource;
use App\Models\Student;
use Filament\Resources\Pages\CreateRecord;

class CreateReferral extends CreateRecord
{
    protected static string $resource = ReferralResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generar código si no fue proporcionado
        if (empty($data['referral_code'])) {
            $referrer = Student::find($data['referrer_student_id']);
            $base     = $referrer ? strtoupper($referrer->code ?? 'REF') : 'REF';
            $suffix   = strtoupper(substr(uniqid(), -4));
            $data['referral_code'] = "{$base}-{$suffix}";
        }

        return $data;
    }
}