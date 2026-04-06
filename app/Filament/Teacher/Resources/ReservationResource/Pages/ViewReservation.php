<?php

namespace App\Filament\Teacher\Resources\ReservationResource\Pages;

use App\Filament\Teacher\Resources\ReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewReservation extends ViewRecord
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Teachers no pueden editar
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Reservación')
                    ->schema([
                        Infolists\Components\TextEntry::make('agenda.name')
                            ->label('Agenda'),
                        Infolists\Components\TextEntry::make('date')
                            ->label('Fecha')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('start_time')
                            ->label('Hora de inicio')
                            ->time('H:i'),
                        Infolists\Components\TextEntry::make('end_time')
                            ->label('Hora de fin')
                            ->time('H:i'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(function ($state) {
                                return match ($state) {
                                    'pending' => 'Pendiente',
                                    'active' => 'Activo',
                                    'confirmed' => 'Confirmado',
                                    'cancelled' => 'Cancelado',
                                    'no_show' => 'No presentado',
                                    'sanctioned' => 'Sancionado',
                                    default => $state,
                                };
                            })
                            ->color(fn($state) => match ($state) {
                                'pending' => 'warning',
                                'active' => 'primary',
                                'confirmed' => 'success',
                                'cancelled' => 'gray',
                                'no_show', 'sanctioned' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('purpose')
                            ->label('Propósito')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Check-in / Check-out')
                    ->schema([
                        Infolists\Components\TextEntry::make('meta.check_in.at')
                            ->label('Check-in')
                            ->placeholder('No registrado')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : null),
                        Infolists\Components\TextEntry::make('meta.check_out.at')
                            ->label('Check-out')
                            ->placeholder('No registrado')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : null),
                    ])->columns(2)
                    ->visible(fn($record) => $record->agenda->requires_qr),

                Infolists\Components\Section::make('Cancelación')
                    ->schema([
                        Infolists\Components\TextEntry::make('meta.cancellation.reason')
                            ->label('Motivo')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('meta.cancellation.at')
                            ->label('Fecha de cancelación')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->visible(fn($record) => !empty($record->meta['cancellation'])),

                Infolists\Components\Section::make('Sanciones')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('meta.sanctions')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('type')
                                    ->label('Tipo')
                                    ->formatStateUsing(fn($state) => $state === 'no_show' ? 'No se presentó' : 'No registró salida'),
                                Infolists\Components\TextEntry::make('sent_at')
                                    ->label('Enviado')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(2),
                    ])
                    ->visible(fn($record) => $record->hasSanction())
                    ->collapsible(),
            ]);
    }
}

