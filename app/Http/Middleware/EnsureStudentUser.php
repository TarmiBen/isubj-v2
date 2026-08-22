<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que el token autenticado pertenezca a un usuario ligado a un Student
 * activo, y deja al alumno disponible en $request->student() para que los
 * controladores nunca tengan que resolverlo (ni confiar en un id del payload).
 */
class EnsureStudentUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->userable_type !== Student::class || ! $user->userable_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $student = Student::where('id', $user->userable_id)
            ->where('status', 'active')
            ->first();

        if (! $student) {
            return response()->json(['message' => 'Cuenta de alumno no disponible.'], 403);
        }

        $request->attributes->set('student', $student);

        return $next($request);
    }
}
