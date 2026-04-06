<?php

namespace App\Filament\Resources\AlertResource\Pages;

use App\Filament\Resources\AlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewAlert extends ViewRecord
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Alerta')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Título')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('message')
                            ->label('Mensaje')
                            ->markdown()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'info' => 'Información',
                                'warning' => 'Advertencia',
                                'danger' => 'Peligro',
                                'success' => 'Éxito',
                                default => $state
                            })
                            ->color(fn ($state) => match($state) {
                                'info' => 'info',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                'success' => 'success',
                                default => 'gray'
                            }),

                        Infolists\Components\TextEntry::make('priority')
                            ->label('Prioridad')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'low' => 'Baja',
                                'medium' => 'Media',
                                'high' => 'Alta',
                                default => $state
                            })
                            ->color(fn ($state) => match($state) {
                                'low' => 'success',
                                'medium' => 'warning',
                                'high' => 'danger',
                                default => 'gray'
                            }),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Estado')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Creado por')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de creación')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('expires_at')
                            ->label('Fecha de vencimiento')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-clock')
                            ->placeholder('Sin vencimiento')
                            ->color(fn ($record) => $record->expires_at && $record->expires_at < now() ? 'danger' : 'gray'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Estadísticas')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_recipients')
                            ->label('Total de destinatarios')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-users'),

                        Infolists\Components\TextEntry::make('viewed_count')
                            ->label('Alertas vistas')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-o-eye')
                            ->formatStateUsing(fn ($record) => $record->viewed_count . ' (' . $record->viewed_percentage . '%)'),

                        Infolists\Components\TextEntry::make('closed_count')
                            ->label('Alertas cerradas')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-o-x-mark')
                            ->formatStateUsing(fn ($record) => $record->closed_count . ' (' . $record->closed_percentage . '%)'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Destinatarios y su estado')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('users')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Usuario'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope'),

                                Infolists\Components\IconEntry::make('pivot.viewed_at')
                                    ->label('Vista')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->getStateUsing(fn ($record) => $record->pivot->viewed_at !== null),

                                Infolists\Components\TextEntry::make('pivot.viewed_at')
                                    ->label('Fecha de vista')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-o-eye'),

                                Infolists\Components\IconEntry::make('pivot.closed_at')
                                    ->label('Cerrada')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->getStateUsing(fn ($record) => $record->pivot->closed_at !== null),

                                Infolists\Components\TextEntry::make('pivot.closed_at')
                                    ->label('Fecha de cierre')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-o-x-mark'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
