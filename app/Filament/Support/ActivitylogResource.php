<?php

namespace App\Filament\Support;

// Vive fuera de app/Filament/Resources a propósito: ese directorio se
// autoregistra completo vía discoverResources() en AdminPanelProvider, y esta
// clase ya se registra explícitamente a través de
// ActivitylogPlugin::make()->resource(...). Si viviera ahí, Filament la
// registraría dos veces (auto-discovery + plugin) sobre el mismo slug
// 'activitylogs' y chocarían las rutas.
use App\Filament\Support\ActivitylogResource\Pages;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Resources\ActivitylogResource as BaseActivitylogResource;

class ActivitylogResource extends BaseActivitylogResource
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getLogNameColumnComponent(),
                static::getEventColumnComponent(),
                static::getSubjectTypeColumnComponent(),
                static::getCauserColumnComponent(),
                static::getPropertiesColumnComponent(),
                static::getCreatedAtColumnComponent(),
            ])
            ->defaultSort(
                config('filament-activitylog.resources.default_sort_column', 'created_at'),
                config('filament-activitylog.resources.default_sort_direction', 'desc')
            )
            ->filters([
                static::getDateFilterComponent(),
                static::getEventFilterComponent(),
            ]);
    }

    // "Quién": nombre en badge con color estable por causante (para
    // distinguirlos de un vistazo en una bitácora larga) y su rol como
    // subtítulo, en vez de solo el nombre en texto plano.
    public static function getCauserColumnComponent(): TextColumn
    {
        return TextColumn::make('causer.name')
            ->label(__('activitylog::tables.columns.causer.label'))
            ->badge()
            ->color(function (Model $record) {
                if (! $record->causer_id) {
                    return 'gray';
                }

                $palette = ['primary', 'success', 'warning', 'danger', 'info', 'gray'];

                return $palette[$record->causer_id % count($palette)];
            })
            ->formatStateUsing(function (Model $record) {
                if ($record->causer_id === null || $record->causer === null) {
                    return $record->causer_id ? 'Usuario eliminado' : 'Sistema (consola)';
                }

                return $record->causer->name;
            })
            ->description(function (Model $record) {
                if (! $record->causer_id || $record->causer === null) {
                    return null;
                }

                $role = method_exists($record->causer, 'getRoleNames')
                    ? $record->causer->getRoleNames()->first()
                    : null;

                return $role ? ucfirst($role) : null;
            })
            ->searchable();
    }

    // "Qué": la columna de propiedades ya trae el diff campo-por-campo (ver
    // el blade publicado en resources/views/vendor/activitylog/...), pero
    // venía oculta por defecto — el punto completo de la bitácora es ver qué
    // cambió sin tener que activarla primero.
    public static function getPropertiesColumnComponent(): \Filament\Tables\Columns\Column
    {
        return parent::getPropertiesColumnComponent()->toggleable(isToggledHiddenByDefault: false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivitylog::route('/'),
            'view'  => Pages\ViewActivitylog::route('/{record}'),
        ];
    }
}
