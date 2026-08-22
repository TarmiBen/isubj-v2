<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /** Agendas reservables (type=room), no las de tipo calendario. */
    public function index(Request $request)
    {
        $agendas = Agenda::where('type', 'room')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($agendas->map(fn (Agenda $agenda) => [
            'id' => $agenda->id,
            'name' => $agenda->name,
            'description' => $agenda->description,
            'color' => $agenda->color,
            'icon' => $agenda->icon,
            'available_days' => $agenda->available_days,
            'open_time' => $agenda->open_time,
            'close_time' => $agenda->close_time,
            'requires_qr' => (bool) $agenda->requires_qr,
        ]));
    }
}
