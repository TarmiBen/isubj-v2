<?php

namespace App\Filament\Teacher\Resources\UserQRResource\Pages;

use App\Filament\Teacher\Resources\UserQRResource;
use Filament\Resources\Pages\Page;

class ScanQr extends Page
{
    protected static string $resource = UserQRResource::class;

    protected static string $view = 'filament.teacher.resources.user-q-r-resource.pages.scan-qr';
}
