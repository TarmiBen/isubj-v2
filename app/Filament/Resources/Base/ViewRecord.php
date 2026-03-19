<?php

namespace App\Filament\Resources\Base;

use Filament\Resources\Pages\ViewRecord as BaseViewRecord;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;

class ViewRecord extends BaseViewRecord
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

    /**
     * Método helper para configurar acciones como dropdown
     */
    protected function configureActionsAsDropdown(): array
    {
        return [
            'actionsColumnLabel' => 'Acciones',
            'actionsPosition' => 'after',
        ];
    }
}
