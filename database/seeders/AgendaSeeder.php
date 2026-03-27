<?php

namespace Database\Seeders;

use App\Models\Agenda;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Agenda::create([
            'name'           => 'Laboratorio de Cómputo',
            'slug'           => 'laboratorio-computo',
            'description'    => 'Laboratorio principal de cómputo para prácticas de programación',
            'type'           => 'room',
            'requires_qr'    => true,
            'qr_room_code'   => Str::uuid()->toString(),
            'is_active'      => true,
            'available_days' => json_encode([1, 2, 3, 4, 5]),
            'open_time'      => '07:00',
            'close_time'     => '21:00',
            'color'          => '#0070C0',
            'capacity'       => 30,
            'icon'           => 'heroicon-o-computer-desktop',
        ]);

        Agenda::create([
            'name'           => 'Laboratorio de Electrónica',
            'slug'           => 'laboratorio-electronica',
            'description'    => 'Laboratorio de electrónica y circuitos',
            'type'           => 'room',
            'requires_qr'    => true,
            'qr_room_code'   => Str::uuid()->toString(),
            'is_active'      => true,
            'available_days' => json_encode([1, 2, 3, 4, 5]),
            'open_time'      => '08:00',
            'close_time'     => '18:00',
            'color'          => '#FF6B35',
            'capacity'       => 25,
            'icon'           => 'heroicon-o-bolt',
        ]);

        Agenda::create([
            'name'           => 'Calendario de Evaluaciones',
            'slug'           => 'evaluaciones',
            'description'    => 'Calendario para registro de exámenes y evaluaciones',
            'type'           => 'calendar',
            'requires_qr'    => false,
            'is_active'      => true,
            'available_days' => json_encode([1, 2, 3, 4, 5]),
            'color'          => '#7B2D8B',
            'icon'           => 'heroicon-o-clipboard-document-check',
        ]);

        Agenda::create([
            'name'           => 'Calendario Escolar',
            'slug'           => 'calendario-escolar',
            'description'    => 'Eventos y actividades institucionales',
            'type'           => 'calendar',
            'requires_qr'    => false,
            'is_active'      => true,
            'available_days' => json_encode([1, 2, 3, 4, 5, 6, 7]),
            'color'          => '#2E7D32',
            'icon'           => 'heroicon-o-calendar',
        ]);
    }
}
