<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EditAgenda extends EditRecord
{
    protected static string $resource = AgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadQr')
                ->label('Descargar QR')
                ->icon('heroicon-o-qr-code')
                ->visible(fn() => $this->record->hasPhysicalQr())
                ->action(function () {
                    $qr = QrCode::format('png')->size(300)->generate($this->record->qr_room_code);
                    return response()->streamDownload(
                        fn() => print($qr),
                        "qr-{$this->record->slug}.png",
                        ['Content-Type' => 'image/png']
                    );
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
