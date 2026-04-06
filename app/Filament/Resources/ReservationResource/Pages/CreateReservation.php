<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Agenda;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateReservation extends CreateRecord
{
    protected static string $resource = ReservationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $agenda = Agenda::find($data['agenda_id']);

        // Establecer horario completo si es todo el día
        if (!empty($data['all_day'])) {
            $data['start_time'] = '00:00:00';
            $data['end_time'] = '23:59:59';
        }

        // Si es tipo calendario y tiene rango de fechas
        if ($agenda && $agenda->type === 'calendar' && isset($data['start_date']) && isset($data['end_date'])) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            // Crear una reservación por cada día en el rango
            $currentDate = $startDate->copy();
            $created = 0;
            $firstReservation = null;

            while ($currentDate <= $endDate) {
                $reservation = Reservation::create([
                    'agenda_id' => $data['agenda_id'],
                    'user_id' => null, // Sin docente para calendarios
                    'date' => $currentDate->format('Y-m-d'),
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'status' => $data['status'],
                    'purpose' => $data['purpose'],
                    'meta' => [
                        'is_calendar_event' => true,
                        'all_day' => !empty($data['all_day']),
                        'event_range' => [
                            'start' => $startDate->format('Y-m-d'),
                            'end' => $endDate->format('Y-m-d'),
                        ],
                    ],
                ]);

                if (!$firstReservation) {
                    $firstReservation = $reservation;
                }

                $created++;
                $currentDate->addDay();
            }

            Notification::make()
                ->title("Evento creado exitosamente")
                ->body("Se crearon {$created} días de evento")
                ->success()
                ->send();

            return $firstReservation;
        }

        // Para salas físicas, crear una sola reservación
        return static::getModel()::create([
            'agenda_id' => $data['agenda_id'],
            'user_id' => $data['user_id'] ?? null,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'purpose' => $data['purpose'],
            'meta' => [
                'all_day' => !empty($data['all_day']),
            ],
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
