<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReservationService
{
    /**
     * Crear una nueva reservación con validaciones
     */
    public function create(array $data, User $user): Reservation
    {
        // Validar que la hora de inicio sea antes de la hora de fin
        if ($data['start_time'] >= $data['end_time']) {
            throw new \Exception('La hora de inicio debe ser anterior a la hora de fin.');
        }

        // Validar que no haya empalmes con otras reservaciones del mismo usuario
        $userConflict = Reservation::where('user_id', $user->id)
            ->where('agenda_id', '!=', $data['agenda_id'])
            ->where('date', $data['date'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_time', '<=', $data['start_time'])
                          ->where('end_time', '>=', $data['end_time']);
                    });
            })
            ->exists();

        if ($userConflict) {
            throw new \Exception('Ya tienes otra reservación en ese horario.');
        }

        // Validar que no haya empalmes en la misma agenda
        $agendaConflict = Reservation::where('agenda_id', $data['agenda_id'])
            ->where('date', $data['date'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_time', '<=', $data['start_time'])
                          ->where('end_time', '>=', $data['end_time']);
                    });
            })
            ->exists();

        if ($agendaConflict) {
            throw new \Exception('El laboratorio ya está reservado en ese horario.');
        }

        // Crear la reservación
        $reservation = Reservation::create([
            'user_id' => $data['user_id'],
            'agenda_id' => $data['agenda_id'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'] ?? null,
            'status' => 'pending',
            'meta' => [],
        ]);

        return $reservation;
    }

    /**
     * Procesar escaneo de código QR
     */
    public function processQrScan(string $qrCode, User $user, $photo = null): array
    {
        // Buscar la agenda por código QR
        $agenda = Agenda::where('qr_room_code', $qrCode)
            ->where('is_active', true)
            ->first();

        if (!$agenda) {
            throw new \Exception('Código QR inválido o agenda no encontrada.');
        }

        // Buscar reservación activa para hoy
        $reservation = Reservation::where('user_id', $user->id)
            ->where('agenda_id', $agenda->id)
            ->whereDate('date', today())
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if (!$reservation) {
            throw new \Exception('No tienes una reservación activa para este laboratorio hoy.');
        }

        $now = Carbon::now();
        $meta = $reservation->meta ?? [];

        // Verificar si ya hizo check-in
        if (empty($meta['check_in'])) {
            // Hacer check-in
            $meta['check_in'] = [
                'timestamp' => $now->toDateTimeString(),
                'scanned_by' => $user->name,
            ];

            if ($photo) {
                $meta['check_in']['photo'] = $photo;
            }

            $reservation->update([
                'status' => 'active',
                'meta' => $meta,
            ]);

            return [
                'action' => 'check_in',
                'reservation' => $reservation,
            ];
        }

        // Verificar si ya hizo check-out
        if (!empty($meta['check_out'])) {
            throw new \Exception('Ya registraste tu salida previamente.');
        }

        // Hacer check-out
        $meta['check_out'] = [
            'timestamp' => $now->toDateTimeString(),
            'scanned_by' => $user->name,
        ];

        if ($photo) {
            $meta['check_out']['photo'] = $photo;
        }

        $reservation->update([
            'status' => 'confirmed',
            'meta' => $meta,
        ]);

        return [
            'action' => 'check_out',
            'reservation' => $reservation,
        ];
    }

    /**
     * Cancelar una reservación
     */
    public function cancel(Reservation $reservation, User $user, string $reason): void
    {
        $meta = $reservation->meta ?? [];

        $meta['cancellation'] = [
            'reason' => $reason,
            'at' => Carbon::now()->toDateTimeString(),
            'by' => $user->id,
            'by_name' => $user->name,
        ];

        $reservation->update([
            'status' => 'cancelled',
            'meta' => $meta,
        ]);
    }
}

