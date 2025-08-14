<?php
namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class StudentImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Student([
            'student_number' => $row['numero_de_estudiante'] ?? null,
            'name' => $row['nombre'] ?? 'Sin nombre',
            'last_name1' => $row['apellido_paterno'] ?? 'sin apellido',
            'last_name2' => $row['apellido_materno'] ?? 'sin apellido',
            'gender' => isset($row['genero'])
                ? [
                'masculino' => 'M',
                'femenino' => 'F',
                'Otro' => 'O',
            ][$row['genero']] ?? (in_array($row['genero'], ['M', 'F', 'O']) ? $row['genero'] : 'O') : 'O',
            'date_of_birth' => isset($row['fecha_de_nacimiento'])
                ? (is_numeric($row['fecha_de_nacimiento'])
                    ? Date::excelToDateTimeObject($row['fecha_de_nacimiento'])->format('Y-m-d')
                    : (strtotime($row['fecha_de_nacimiento']) !== false
                        ? Carbon::parse($row['fecha_de_nacimiento'])->format('Y-m-d')
                        : null))
                : null,
            'curp' => $row['curp'] ?? 'sin curp',
            'email' => $row['correo'] ?? 'sin correo',
            'phone' => $row['telefono'] ?? 'sin telefono',
            'street' => $row['calle'] ?? 'sin calle',
            'city' => $row['ciudad'] ?? 'sin ciudad',
            'state' => $row['estado'] ?? 'sin estado',
            'postal_code' => $row['codigo_postal'] ?? '00000',
            'country' => $row['pais'] ?? 'México',
            'enrollment_date' => isset($row['fecha_de_inscripcion'])
                ? (is_numeric($row['fecha_de_inscripcion'])
                    ? Date::excelToDateTimeObject($row['fecha_de_inscripcion'])->format('Y-m-d')
                    : (strtotime($row['fecha_de_inscripcion']) !== false
                        ? Carbon::parse($row['fecha_de_inscripcion'])->format('Y-m-d')
                        : Carbon::parse('2000-01-01')->format('Y-m-d')))
                : Carbon::parse('0000-00-00')->format('Y-m-d'),
            'status' => isset($row['estado_alumno'])
                ? [
                'activo' => 'active',
                'inactivo' => 'inactive',
                'graduado' => 'graduated',
                'suspendido' => 'suspended',
            ][$row['estado_alumno']] ?? $row['estado_alumno']
                : 'inactive',
            'guardian_name' => $row['tutor'] ?? 'sin tutor',
            'guardian_phone' => $row['telefono_del_tutor'] ?? '0000000000',
            'emergency_contact_name' => $row['nombre_de_contacto_de_emergencia'] ?? 'Emergencia',
            'emergency_contact_phone' => $row['telefono_del_contacto_de_emergencia'] ?? '0000000000',
            'code' =>  123,
        ]);
    }
    public function rules(): array
    {
        return [
            'numero_de_estudiante' => ['required', 'string', 'max:20', 'unique:students,student_number'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['required', 'string', 'max:100'],
            'genero' => ['required', Rule::in(['M', 'F', 'O'])],
            'fecha_de_nacimiento' => ['required', 'date' ,'before:today'],
            'curp' => ['required', 'string', 'size:18', 'unique:students,curp'],
            'correo' => ['required', 'email', 'max:150', 'unique:students,email'],
            'telefono' => ['required', 'string', 'max:15'],
            'calle' => ['required', 'string', 'max:100'],
            'ciudad' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:6'],
            'pais' => ['required', 'string', 'max:100'],
            'fecha_de_inscripcion' => ['required', 'date', 'before_or_equal:today'],
            'estado_alumno' => ['required', Rule::in(['active', 'inactive', 'graduated', 'suspended', 'pre-registration'])],
            'tutor' => ['nullable', 'string', 'max:150'],
            'telefono_del_tutor' => ['nullable', 'string', 'max:15'],
            'nombre_de_contacto_de_emergencia' => ['nullable', 'string', 'max:150'],
            'telefono_del_contacto_de_emergencia' => ['nullable', 'string', 'max:15'],
        ];
    }

}
