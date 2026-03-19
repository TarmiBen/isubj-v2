<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Cycle;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario admin
        User::firstOrCreate([
            'email' => 'admin@test.com'
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('password123'),
            'email_verified_at' => now()
        ]);

        // Crear ciclo activo si no existe
        $cycle = Cycle::firstOrCreate([
            'code' => '1'
        ], [
            'name' => 'Ciclo Enero-Junio 2024',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'active' => true,
            'description' => 'Primer ciclo de evaluación docente'
        ]);

        // Crear docentes
        $teachers = [
            ['name' => 'Dr. Juan Pérez', 'email' => 'juan.perez@escuela.edu'],
            ['name' => 'Dra. María González', 'email' => 'maria.gonzalez@escuela.edu'],
            ['name' => 'Lic. Carlos Rodríguez', 'email' => 'carlos.rodriguez@escuela.edu'],
            ['name' => 'Ing. Ana López', 'email' => 'ana.lopez@escuela.edu'],
        ];

        foreach ($teachers as $teacherData) {
            Teacher::firstOrCreate(['email' => $teacherData['email']], $teacherData);
        }

        // Crear materias
        $subjects = [
            ['code' => 'MAT101', 'name' => 'Matemáticas I'],
            ['code' => 'FIS101', 'name' => 'Física I'],
            ['code' => 'QUI101', 'name' => 'Química General'],
            ['code' => 'ING101', 'name' => 'Introducción a la Ingeniería'],
            ['code' => 'CAL101', 'name' => 'Cálculo Diferencial'],
        ];

        foreach ($subjects as $subjectData) {
            Subject::firstOrCreate(['code' => $subjectData['code']], $subjectData);
        }

        // Crear grupo
        $group = Group::firstOrCreate([
            'code' => 'GRP-2024-1'
        ], [
            'status' => 'active',
            'meta' => json_encode(['semester' => 1, 'year' => 2024])
        ]);

        // Crear estudiantes
        $students = [
            ['code' => 'EST001', 'name' => 'Juan', 'last_name1' => 'Martínez', 'last_name2' => 'Silva', 'email' => 'juan.martinez@estudiante.edu'],
            ['code' => 'EST002', 'name' => 'María', 'last_name1' => 'López', 'last_name2' => 'García', 'email' => 'maria.lopez@estudiante.edu'],
            ['code' => 'EST003', 'name' => 'Carlos', 'last_name1' => 'Hernández', 'last_name2' => 'Ruiz', 'email' => 'carlos.hernandez@estudiante.edu'],
            ['code' => 'EST004', 'name' => 'Ana', 'last_name1' => 'Torres', 'last_name2' => 'Morales', 'email' => 'ana.torres@estudiante.edu'],
            ['code' => 'EST005', 'name' => 'Luis', 'last_name1' => 'Vargas', 'last_name2' => 'Castillo', 'email' => 'luis.vargas@estudiante.edu'],
        ];

        foreach ($students as $studentData) {
            $student = Student::firstOrCreate(['code' => $studentData['code']], array_merge($studentData, [
                'gender' => 'M',
                'date_of_birth' => '2000-01-01',
                'curp' => 'CURP' . $studentData['code'] . '001',
                'phone' => '1234567890',
                'street' => 'Calle Principal 123',
                'city' => 'Ciudad',
                'state' => 'Estado',
                'postal_code' => '12345',
                'country' => 'México',
                'status' => 'active'
            ]));

            // Inscribir estudiante al grupo
            Inscription::firstOrCreate([
                'student_id' => $student->id,
                'group_id' => $group->id
            ], [
                'status' => 'active'
            ]);
        }

        // Crear asignaciones (docente-materia-grupo)
        $teachers = Teacher::all();
        $subjects = Subject::all();

        for ($i = 0; $i < min(count($teachers), count($subjects)); $i++) {
            Assignment::firstOrCreate([
                'group_id' => $group->id,
                'subject_id' => $subjects[$i]->id,
                'teacher_id' => $teachers[$i]->id
            ]);
        }

        $this->command->info('Datos de prueba creados exitosamente:');
        $this->command->info('- Usuario admin: admin@test.com / password123');
        $this->command->info('- ' . count($students) . ' estudiantes con códigos EST001-EST005');
        $this->command->info('- ' . count($teachers) . ' docentes');
        $this->command->info('- ' . count($subjects) . ' materias');
        $this->command->info('- 1 grupo con ' . Assignment::count() . ' asignaciones');
    }
}
