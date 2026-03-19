<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Lista de recursos que necesitan permisos
        $resources = [
            'payment',
            'payment::order',
            'payment::concept',
            'monthly::fee::config',
            'discount',
            'referral',
            'agreement',
            'cycle',
            'survey',
            'survey::question',
            'question::option',
        ];

        // Prefijos de permisos de Filament
        $prefixes = [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ];

        $guard = 'web';

        foreach ($resources as $resource) {
            foreach ($prefixes as $prefix) {
                $permissionName = "{$prefix}_{$resource}";

                // Crear el permiso si no existe
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => $guard],
                    ['name' => $permissionName, 'guard_name' => $guard]
                );

                $this->command->info("Permiso creado/verificado: {$permissionName}");
            }
        }

        $this->command->info('¡Permisos generados correctamente!');
    }
}

