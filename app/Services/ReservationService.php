<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReservationService
{
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

