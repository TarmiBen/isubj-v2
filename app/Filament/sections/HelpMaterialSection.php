<?php

namespace App\Filament\sections;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;

class HelpMaterialSection
{
    public static function make(): Section
    {
        return Section::make('Material de Ayuda de la Materia')
        ->schema([
            RepeatableEntry::make('subject.documents')
                ->label('')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('name')
                                ->label('Nombre del Documento')
                                ->weight(FontWeight::SemiBold)
                                ->icon('heroicon-o-document-text')
                                ->color('primary'),

                            TextEntry::make('src')
                                ->label('Archivo')
                                ->url(fn ($record) => asset('storage/' . $record->src))
                                ->openUrlInNewTab()
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('success')
                                ->formatStateUsing(fn () => 'Descargar'),

                            TextEntry::make('created_at')
                                ->label('Fecha de Carga')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-o-clock'),
                        ]),
                ])
                ->contained(false)
                ->columnSpanFull()
                ->hidden(fn ($record) => $record->subject->documents->isEmpty()),

            Group::make()
                ->schema([
                    TextEntry::make('no_subject_documents')
                        ->label('')
                        ->default('No hay material de ayuda disponible para esta materia')
                        ->color('gray')
                        ->icon('heroicon-o-folder-open'),
                ])
                ->hidden(fn ($record) => $record->subject->documents->isNotEmpty()),
        ])
            ->collapsible()
            ->collapsed()
            ->icon('heroicon-o-document-duplicate')
            ->description('Material de ayuda y documentos de referencia de la materia')
            ->hidden(fn ($record) => $record->subject->documents->isEmpty());
    }
}
