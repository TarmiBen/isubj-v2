<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Solo estos campos son editables por el propio alumno: teléfono, correo
 * (dirección electrónica) y dirección física. Todo lo demás (nombre, CURP,
 * generación, status, etc.) requiere trámite en control escolar.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('students', 'email')->ignore($this->user()->userable_id),
            ],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }
}
