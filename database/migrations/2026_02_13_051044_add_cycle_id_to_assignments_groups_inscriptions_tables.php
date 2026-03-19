<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Obtener el ciclo activo o el primer ciclo disponible
        $activeCycle = DB::table('cycles')->where('active', true)->first();
        if (!$activeCycle) {
            $activeCycle = DB::table('cycles')->first();
        }

        // Agregar cycle_id a assignments
        Schema::table('assignments', function (Blueprint $table) use ($activeCycle) {
            if (!Schema::hasColumn('assignments', 'cycle_id')) {
                $table->unsignedBigInteger('cycle_id')->nullable()->after('subject_id');
            }
        });

        // Actualizar registros existentes con el ciclo activo
        if ($activeCycle) {
            DB::table('assignments')->whereNull('cycle_id')->update(['cycle_id' => $activeCycle->id]);
        }

        // Hacer el campo no nullable
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('cycle_id')->nullable(false)->change();
        });

        // Agregar cycle_id a groups
        Schema::table('groups', function (Blueprint $table) use ($activeCycle) {
            if (!Schema::hasColumn('groups', 'cycle_id')) {
                $table->unsignedBigInteger('cycle_id')->nullable()->after('generation_id');
            }
        });

        // Actualizar registros existentes con el ciclo activo
        if ($activeCycle) {
            DB::table('groups')->whereNull('cycle_id')->update(['cycle_id' => $activeCycle->id]);
        }

        // Hacer el campo no nullable
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('cycle_id')->nullable(false)->change();
        });

        // Agregar cycle_id a inscriptions
        Schema::table('inscriptions', function (Blueprint $table) use ($activeCycle) {
            if (!Schema::hasColumn('inscriptions', 'cycle_id')) {
                $table->unsignedBigInteger('cycle_id')->nullable()->after('group_id');
            }
        });

        // Actualizar registros existentes con el ciclo activo
        if ($activeCycle) {
            DB::table('inscriptions')->whereNull('cycle_id')->update(['cycle_id' => $activeCycle->id]);
        }

        // Hacer el campo no nullable
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('cycle_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'cycle_id')) {
                $table->dropColumn('cycle_id');
            }
        });

        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'cycle_id')) {
                $table->dropColumn('cycle_id');
            }
        });

        Schema::table('inscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('inscriptions', 'cycle_id')) {
                $table->dropColumn('cycle_id');
            }
        });
    }
};
