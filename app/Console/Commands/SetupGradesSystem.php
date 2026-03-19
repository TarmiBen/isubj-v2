<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SetupGradesSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:grades-system {--assign-to-all : Asignar permiso a todos los usuarios existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configurar el sistema de calificaciones en producción';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configurando Sistema de Calificaciones para Producción...');
        $this->line('');

        // Crear el permiso
        $this->info('⏳ Creando permiso edit_qualifications...');
        if ($this->createPermission()) {
            $this->info('   ✅ Permiso creado exitosamente');
        } else {
            $this->error('   ❌ Error creando permiso');
            return 1;
        }

        // Verificar tablas necesarias
        $this->info('⏳ Verificando tablas de la base de datos...');
        if ($this->verifyTables()) {
            $this->info('   ✅ Todas las tablas están disponibles');
        } else {
            $this->error('   ❌ Error verificando tablas');
            return 1;
        }

        // Asignar permisos si se solicita
        if ($this->option('assign-to-all')) {
            $this->info('⏳ Asignando permisos a usuarios existentes...');
            if ($this->assignPermissionsToUsers()) {
                $this->info('   ✅ Permisos asignados exitosamente');
            } else {
                $this->error('   ❌ Error asignando permisos');
            }
        }

        $this->line('');
        $this->info('✅ Sistema de Calificaciones configurado exitosamente!');
        $this->line('');

        $this->displayUsageInstructions();
        return 0;
    }

    private function createPermission()
    {
        try {
            Permission::firstOrCreate([
                'name' => 'edit_qualifications',
                'guard_name' => 'web'
            ]);
            return true;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return false;
        }
    }

    private function verifyTables()
    {
        try {
            $tables = ['qualifications', 'permissions', 'students', 'assignments', 'units'];

            foreach ($tables as $table) {
                if (!\Schema::hasTable($table)) {
                    $this->error("Tabla '{$table}' no existe");
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->error('Error verificando tablas: ' . $e->getMessage());
            return false;
        }
    }

    private function assignPermissionsToUsers()
    {
        try {
            $permission = Permission::where('name', 'edit_qualifications')->first();
            if (!$permission) {
                return false;
            }

            $users = User::all();
            $assigned = 0;

            foreach ($users as $user) {
                if (!$user->hasPermissionTo('edit_qualifications')) {
                    $user->givePermissionTo('edit_qualifications');
                    $assigned++;
                }
            }

            $this->info("   → {$assigned} usuarios obtuvieron el permiso");
            return true;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return false;
        }
    }

    private function displayUsageInstructions()
    {
        $this->comment('📋 Instrucciones de Uso:');
        $this->line('');
        $this->line('1. 🔐 Gestionar Permisos:');
        $this->line('   • Panel Admin → Roles & Permisos → Asignar "edit_qualifications"');
        $this->line('   • O manualmente: $user->givePermissionTo("edit_qualifications")');
        $this->line('');
        $this->line('2. 📊 Usar el Sistema:');
        $this->line('   • Ir a Assignments → Ver cualquier assignment');
        $this->line('   • Buscar botón "Calificar" en tabla de estudiantes');
        $this->line('   • Ingresar calificaciones por unidad');
        $this->line('');
        $this->line('3. ⚡ Comandos Útiles:');
        $this->line('   • php artisan setup:grades-system --assign-to-all');
        $this->line('   • php artisan db:seed --class=EditQualificationsPermissionSeeder');
        $this->line('');
        $this->comment('🎯 Sistema listo para uso en producción!');
    }
}
