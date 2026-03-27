<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Page;

class ScanQr extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Escanear QR';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.teacher.pages.scan-qr';
}

