<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ChangePasswordRequest;
use App\Http\Requests\Student\SetPasswordRequest;
use App\Http\Resources\Student\StudentResource;
use App\Services\StudentAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly StudentAuthService $auth)
    {
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->auth->attemptLogin($data['identifier'], $data['password']);

        return match ($result['status']) {
            'ok' => response()->json([
                'status' => 'ok',
                'token' => $result['token'],
                'student' => new StudentResource($result['student']),
            ]),
            'must_set_password' => response()->json([
                'status' => 'must_set_password',
                'setup_token' => $result['setup_token'],
            ]),
            'blocked' => response()->json([
                'message' => 'El acceso de alumnos está temporalmente deshabilitado.',
            ], 423),
            default => response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401),
        };
    }

    public function setPassword(SetPasswordRequest $request)
    {
        $result = $this->auth->completePasswordSetup(
            $request->validated('setup_token'),
            $request->validated('password'),
        );

        return match ($result['status']) {
            'ok' => response()->json([
                'status' => 'ok',
                'token' => $result['token'],
                'student' => new StudentResource($result['student']),
            ]),
            'already_registered' => response()->json([
                'message' => 'Esta cuenta ya fue registrada, inicia sesión con tu nueva contraseña.',
            ], 409),
            default => response()->json([
                'message' => 'El enlace expiró. Vuelve a iniciar sesión con tu CURP.',
            ], 410),
        };
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => 'ok']);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        $user->update(['password' => Hash::make($request->validated('password'))]);

        // Cierra las demás sesiones activas por seguridad; deja la actual viva.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['status' => 'ok']);
    }

    public function me(Request $request)
    {
        return new StudentResource($request->student());
    }
}
