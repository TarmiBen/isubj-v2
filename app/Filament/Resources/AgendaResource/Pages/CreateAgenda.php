<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAgenda extends CreateRecord
{
    protected static string $resource = AgendaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar UUID para QR si requires_qr está activo y no hay código
        if (isset($data['requires_qr']) && $data['requires_qr'] && empty($data['qr_room_code'])) {
            $data['qr_room_code'] = Str::uuid()->toString();
        }

        // Guardar el usuario que crea la agenda
        $data['created_by'] = auth()->id();

        return $data;
    }
}
