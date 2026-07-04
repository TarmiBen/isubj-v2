<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;

class CausedActivitiesRelationManager extends ActivitylogRelationManager
{
    protected static string $relationship = 'causedActivities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Mi Actividad';
    }
}