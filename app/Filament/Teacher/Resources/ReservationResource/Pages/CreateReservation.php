<?php

namespace App\Filament\Teacher\Resources\ReservationResource\Pages;

use App\Filament\Teacher\Resources\ReservationResource;
use App\Services\ReservationService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReservation extends CreateRecord
{
    protected static string $resource = ReservationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // El servicio se encargará de crear la reservación con validación
        // Aquí solo agregamos el user_id
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Usar el servicio para crear con validación de empalme y envío de correo
        return app(ReservationService::class)->create($data, auth()->user());
    }
}
