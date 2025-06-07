<?php

namespace App\Imports;

use App\Models\Teacher;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class TeacherImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Teacher([
            'employee_number' => $row['numero_de_empleado'] ?? 8734,
            'first_name' => $row['nombre'] ?? 'Sin nombre',
            'last_name1' => $row['apellido_paterno'] ?? 'Sin apellido',
            'last_name2' => $row['apellido_materno'] ?? 'Sin apellido',
            'gender' => isset($row['genero'])
                ? [
                'masculino' => 'M',
                'femenino' => 'F',
                'otro' => 'O',
            ][$row['genero']] ?? (in_array($row['genero'], ['M', 'F', 'O']) ? $row['genero'] : 'O')
                : 'O',
            'date_of_birth' => isset($row['fecha_de_nacimiento'])
                ? (is_numeric($row['fecha_de_nacimiento'])
                    ? Date::excelToDateTimeObject($row['fecha_de_nacimiento'])->format('Y-m-d')
                    : (strtotime($row['fecha_de_nacimiento']) !== false
                        ? Carbon::parse($row['fecha_de_nacimiento'])->format('Y-m-d')
                        : null))
                : null,
            'curp' => $row['curp'] ?? 'SINCURP',
            'email' => $row['correo'] ?? 'sin@correo.com',
            'phone' => $row['telefono'] ?? '0000000000',
            'mobile' => $row['celular'] ?? '0000000000',
            'hire_date' => isset($row['fecha_de_contratacion'])
                ? (is_numeric($row['fecha_de_contratacion'])
                    ? Date::excelToDateTimeObject($row['fecha_de_contratacion'])->format('Y-m-d')
                    : (strtotime($row['fecha_de_contratacion']) !== false
                        ? \Carbon\Carbon::parse($row['fecha_de_contratacion'])->format('Y-m-d')
                        : \Carbon\Carbon::parse('2000-01-01')->format('Y-m-d')))
                : \Carbon\Carbon::parse('2000-01-01')->format('Y-m-d'),
            'status' => isset($row['estado'])
                ? [
                'activo' => 'active',
                'inactivo' => 'inactive',
                'suspendido' => 'suspended',
                'retirado' => 'retired',
            ][$row['estado']] ?? 'inactive'
                : 'inactive',

            'street' => $row['calle'] ?? 'Sin calle',
            'city' => $row['ciudad'] ?? 'Sin ciudad',
            'state' => $row['estado_localidad'] ?? 'Sin estado',
            'postal_code' => $row['codigo_postal'] ?? '00000',
            'country' => $row['pais'] ?? 'México',

            'title' => $row['titulo'] ?? 'Sin título',
            'specialization' => $row['especializacion'] ?? 'General',
            'emergency_contact_name' => $row['nombre_de_contacto_de_emergencia'] ?? 'Emergencia',
            'emergency_contact_phone' => $row['telefono_de_contacto_de_emergencia'] ?? '0000000000',
        ]);
    }
}
