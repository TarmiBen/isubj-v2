<?php

namespace App\Traits;

use Filament\Tables\Table;
use Filament\Infolists\Infolist;

trait HasDropdownActions
{
    /**
     * Configura las tablas para usar dropdown en las acciones
     */
    protected function configureTableActionsAsDropdown(Table $table): Table
    {
        return $table
            ->actionsColumnLabel('Acciones')
            ->actionsPosition('after')
            ->recordAction(null); // Esto hace que use dropdown por defecto
    }

    /**
     * Hook que se ejecuta automáticamente para configurar tablas
     */
    public function table(Table $table): Table
    {
        return $this->configureTableActionsAsDropdown(parent::table($table));
    }

    /**
     * Configura las infolists para usar dropdown en las acciones
     */
    protected function configureInfolistActionsAsDropdown(Infolist $infolist): Infolist
    {
        return $infolist;
    }

    /**
     * Hook que se ejecuta automáticamente para configurar infolists
     */
    public function infolist(Infolist $infolist): Infolist
    {
        return $this->configureInfolistActionsAsDropdown(parent::infolist($infolist));
    }
}
