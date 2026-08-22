<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Http\Resources\Student\StudentResource;
use App\Services\PhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $student = $request->student()->load('generation.career');

        return new StudentResource($student);
    }

    /**
     * Solo teléfono, correo (dirección electrónica) y dirección física son
     * editables por el alumno. Todo lo demás se gestiona en control escolar.
     */
    public function update(UpdateProfileRequest $request)
    {
        $student = $request->student();

        $student->update($request->validated());

        // Mantener sincronizado el email del User (login) con el del Student.
        $user = $request->user();
        if ($user->email !== $student->email) {
            $user->update(['email' => $student->email]);
        }

        return new StudentResource($student->fresh());
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:15360', 'mimes:jpeg,png,webp'],
        ]);

        $student = $request->student();

        $path = $request->file('photo')->store('students/'.$student->id, 'public');

        PhotoService::optimizeOriginal($path);
        $thumbPath = PhotoService::generateThumbnail($path);

        // Limpia archivos anteriores para no acumular basura en el disco.
        foreach ([$student->photo, $student->photo_thumb] as $old) {
            if ($old && $old !== $path && $old !== $thumbPath) {
                Storage::disk('public')->delete($old);
            }
        }

        $student->update(['photo' => $path, 'photo_thumb' => $thumbPath]);

        return new StudentResource($student->fresh());
    }
}
