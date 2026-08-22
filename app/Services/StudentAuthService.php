<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Login de alumnos por email o CURP contra la app de estudiantes.
 *
 * Primer ingreso: no existe User ligado al Student todavía, así que la
 * "contraseña" aceptada es su CURP (case-insensitive). Si coincide, en vez de
 * autenticarlo de una vez se emite un token de configuración de un solo uso
 * (10 min, guardado en cache) que solo sirve para completePasswordSetup(): el
 * alumno *debe* fijar una contraseña propia antes de tener acceso real.
 */
class StudentAuthService
{
    private const SETUP_CACHE_PREFIX = 'student-setup:';
    private const SETUP_TTL_MINUTES = 10;

    /**
     * @return array{status: string, token?: string, setup_token?: string, student?: Student}
     */
    public function attemptLogin(string $identifier, string $password): array
    {
        $identifier = trim($identifier);

        $student = Student::where('status', 'active')
            ->where(function ($query) use ($identifier) {
                $query->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])
                    ->orWhereRaw('UPPER(curp) = ?', [Str::upper($identifier)]);
            })
            ->first();

        if (! $student) {
            return ['status' => 'invalid'];
        }

        if (Setting::get('block_student_login', false)) {
            return ['status' => 'blocked'];
        }

        $user = $student->user;

        if (! $user) {
            if (! hash_equals(Str::upper($student->curp ?? ''), Str::upper($password))) {
                return ['status' => 'invalid'];
            }

            return [
                'status' => 'must_set_password',
                'setup_token' => $this->issueSetupToken($student),
            ];
        }

        if (! Hash::check($password, $user->password)) {
            return ['status' => 'invalid'];
        }

        return [
            'status' => 'ok',
            'token' => $this->issueAccessToken($user),
            'student' => $student,
        ];
    }

    /**
     * @return array{status: string, token?: string, student?: Student}
     */
    public function completePasswordSetup(string $setupToken, string $password): array
    {
        $studentId = Cache::pull(self::SETUP_CACHE_PREFIX.$setupToken);

        if (! $studentId) {
            return ['status' => 'expired'];
        }

        $student = Student::where('id', $studentId)->where('status', 'active')->first();

        if (! $student) {
            return ['status' => 'expired'];
        }

        // Ya se registró con otra pestaña/petición mientras el token estaba vivo.
        if ($student->user) {
            return ['status' => 'already_registered'];
        }

        $user = User::create([
            'name' => $student->full_name,
            'email' => $student->email,
            'password' => Hash::make($password),
        ]);

        $user->userable()->associate($student);
        $user->save();

        return [
            'status' => 'ok',
            'token' => $this->issueAccessToken($user),
            'student' => $student,
        ];
    }

    private function issueSetupToken(Student $student): string
    {
        $token = Str::random(64);

        Cache::put(self::SETUP_CACHE_PREFIX.$token, $student->id, now()->addMinutes(self::SETUP_TTL_MINUTES));

        return $token;
    }

    private function issueAccessToken(User $user): string
    {
        return $user->createToken('estudiantes-app', ['student'], now()->addHours(12))->plainTextToken;
    }
}
