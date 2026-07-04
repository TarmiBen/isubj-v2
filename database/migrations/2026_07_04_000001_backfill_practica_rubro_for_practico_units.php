<?php

use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Unidades ya marcadas como "practico" que no tenían rubros configurados
     * se quedan sin forma de capturar calificaciones ahora que "Gestionar Rubros"
     * se oculta para ese tipo. Se les asigna el rubro fijo "Práctica" (10 pts).
     */
    public function up(): void
    {
        Unit::query()
            ->whereNotNull('meta')
            ->get()
            ->each(function (Unit $unit) {
                $meta = $unit->meta ?? [];

                if (($meta['tipo'] ?? null) !== 'practico') {
                    return;
                }

                if (!empty($meta['rubros'])) {
                    return;
                }

                $meta['rubros'] = [
                    ['nombre' => 'Práctica', 'valor' => 10],
                ];

                $unit->meta = $meta;
                $unit->saveQuietly();
            });
    }

    /**
     * No reversible: no se puede distinguir un rubro "Práctica" preexistente
     * de uno creado por este backfill.
     */
    public function down(): void
    {
    }
};