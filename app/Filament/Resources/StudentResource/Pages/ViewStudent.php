<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;
    protected static string $view = 'filament.resources.student-resource.pages.view-student';


}
