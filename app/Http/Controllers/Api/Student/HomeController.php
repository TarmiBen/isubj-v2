<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\StudentResource;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $student = $request->student();

        $inscription = $student->inscriptions()
            ->where('status', 'active')
            ->with(['group.assignments.subject'])
            ->latest()
            ->first();

        $subjects = $inscription
            ? $inscription->group->assignments
                ->map(fn ($assignment) => [
                    'id' => $assignment->id,
                    'name' => $assignment->subject?->name,
                ])
                ->values()
            : collect();

        $lastPayment = $student->payments()->latest('payment_date')->first();

        return response()->json([
            'student' => new StudentResource($student),
            'greeting' => $this->greeting(),
            'birthday' => $this->birthdayInfo($student),
            'subjects' => $subjects,
            'last_payment' => $lastPayment ? [
                'folio' => $lastPayment->folio,
                'amount' => (float) $lastPayment->amount_applied,
                'date' => $lastPayment->payment_date?->format('Y-m-d'),
                'status' => $lastPayment->status,
            ] : null,
            'pending_balance' => (float) $student->pending_balance,
        ]);
    }

    private function greeting(): string
    {
        $hour = (int) now('America/Mexico_City')->format('G');

        return match (true) {
            $hour < 12 => 'Buenos días',
            $hour < 19 => 'Buenas tardes',
            default => 'Buenas noches',
        };
    }

    /**
     * Cumpleaños dentro de una ventana de ±5 días calendario, comparando solo
     * mes/día (año no importa) para que funcione cerca del cambio de año.
     */
    private function birthdayInfo(Student $student): array
    {
        if (! $student->date_of_birth) {
            return ['is_near' => false];
        }

        $today = Carbon::now('America/Mexico_City')->startOfDay();
        $dob = $student->date_of_birth;

        $candidates = [
            $dob->copy()->year($today->year),
            $dob->copy()->year($today->year - 1),
            $dob->copy()->year($today->year + 1),
        ];

        $diff = min(array_map(fn (Carbon $c) => abs($today->diffInDays($c, false)), $candidates));

        return [
            'is_near' => $diff <= 5,
            'is_today' => $diff === 0,
        ];
    }
}
