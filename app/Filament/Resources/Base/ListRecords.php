<?php

namespace App\Filament\Resources\Base;

use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Filament\Tables\Table;

class ListRecords extends BaseListRecords
{
    /**
     * Configura automáticamente las tablas para usar dropdown en acciones
     */
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->actionsColumnLabel('Acciones')
            ->actionsPosition('after');
    }
}
