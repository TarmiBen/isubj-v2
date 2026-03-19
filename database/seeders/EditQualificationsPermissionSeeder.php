<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class EditQualificationsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Definir todos los permisos relacionados con calificaciones
        $permissions = [
            'view_any_qualification' => 'Ver listado de calificaciones',
            'view_qualification' => 'Ver calificación individual',
            'create_qualification' => 'Crear calificaciones',
            'edit_qualification' => 'Editar calificaciones',
            'delete_qualification' => 'Eliminar calificaciones',
            'restore_qualification' => 'Restaurar calificaciones',
            'force_delete_qualification' => 'Eliminar permanentemente calificaciones',

            // Permisos adicionales para módulos específicos
            'view_grades_module' => 'Ver módulo de calificaciones',
            'export_grades' => 'Exportar calificaciones',
            'import_grades' => 'Importar calificaciones',
            'manage_student_grades' => 'Gestionar calificaciones de estudiantes',
            'view_grade_reports' => 'Ver reportes de calificaciones',
        ];

        // Crear los permisos si no existen
        foreach ($permissions as $name => $description) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web'
            ]);

            $this->command->info("✅ Permiso '{$name}' creado exitosamente.");
        }

        $this->command->info('📋 Todos los permisos de calificaciones han sido creados.');
        $this->command->info('🎯 Para asignar estos permisos:');
        $this->command->info('   - Ve al panel de administración de Filament Shield');
        $this->command->info('   - Selecciona un rol y asigna los permisos necesarios');
        $this->command->info('   - Los permisos aparecerán en la sección de permisos personalizados');
    }
}
