<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignTeacherPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teacher:assign-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar permisos necesarios al rol Teachers para el panel de teacher';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Asignando permisos al rol Teachers...');

        // Buscar o crear el rol Teachers
        $role = Role::where('name', 'Teachers')->first();
        if (!$role) {
            $role = Role::create(['name' => 'Teachers']);
            $this->info('Rol Teachers creado.');
        }

        // Permisos específicos del panel teacher para assignments
        $teacherPermissions = [
            'view_teacher::assignment',
            'view_any_teacher::assignment'
        ];

        $assigned = 0;
        foreach ($teacherPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();

            if ($permission) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    $this->info("✅ Asignado permiso: {$permissionName}");
                    $assigned++;
                } else {
                    $this->comment("✓ Permiso ya existe: {$permissionName}");
                }
            } else {
                $this->error("❌ Permiso no encontrado: {$permissionName}");
            }
        }

        if ($assigned > 0) {
            $this->info("Se asignaron {$assigned} permisos nuevos al rol Teachers.");
        } else {
            $this->info("Todos los permisos ya estaban asignados.");
        }

        $this->info('Proceso completado. Los teachers ahora deberían poder ver el menú de asignaturas.');

        return 0;
    }
}
