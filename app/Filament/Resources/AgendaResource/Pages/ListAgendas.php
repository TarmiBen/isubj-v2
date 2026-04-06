<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgendas extends ListRecords
{
    protected static string $resource = AgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Ver Calendario')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->url(fn () => AgendaResource::getUrl('calendar')),
            Actions\CreateAction::make(),
        ];
    }
}
