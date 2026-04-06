<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AlertsWidget extends BaseWidget
{
    protected static ?int $sort = -1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): string
    {
        return '🔔 Mis Alertas Activas';
    }

    protected function getTablePollingInterval(): ?string
    {
        return '30s'; // Actualiza cada 30 segundos
    }

    public function mount(): void
    {
        // Marcar automáticamente todas las alertas como vistas al cargar el widget
        $this->markAllAlertsAsViewed();
    }

    protected function markAllAlertsAsViewed(): void
    {
        $alerts = Alert::query()
            ->active()
            ->forUser(auth()->id())
            ->notClosedBy(auth()->id())
            ->get();

        foreach ($alerts as $alert) {
            if (!$alert->isViewedBy(auth()->user())) {
                $alert->markAsViewed(auth()->user());
            }
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Alert::query()
                    ->active()
                    ->forUser(auth()->id())
                    ->notClosedBy(auth()->id())
                    ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('title')
                            ->label('Título')
                            ->weight('bold')
                            ->formatStateUsing(function ($record, $state) {
                                $emoji = match($record->priority) {
                                    'high' => '🔴 ',
                                    'medium' => '🟡 ',
                                    'low' => '🟢 ',
                                    default => ''
                                };
                                return $emoji . $state;
                            })
                            ->color(fn ($record) => match($record->type) {
                                'info' => 'info',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                'success' => 'success',
                                default => 'gray'
                            })
                            ->grow(true),

                        Tables\Columns\BadgeColumn::make('type')
                            ->label('Tipo')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'info' => 'Info',
                                'warning' => 'Advertencia',
                                'danger' => 'Peligro',
                                'success' => 'Éxito',
                                default => $state
                            })
                            ->colors([
                                'primary' => 'info',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                'success' => 'success'
                            ])
                            ->grow(false),
                    ]),

                    Tables\Columns\TextColumn::make('message')
                        ->label('Mensaje')
                        ->limit(150)
                        ->wrap()
                        ->color(fn ($record) => match($record->type) {
                            'info' => 'info',
                            'warning' => 'warning',
                            'danger' => 'danger',
                            'success' => 'success',
                            default => 'gray'
                        }),
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->actions([

                Tables\Actions\Action::make('close')
                    ->label('Cerrar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->markAsClosed(auth()->user());
                        $this->dispatch('alert-closed');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cerrar alerta')
                    ->modalDescription('¿Estás seguro de que quieres cerrar esta alerta? Ya no se mostrará en tu dashboard.')
                    ->modalSubmitActionLabel('Sí, cerrar'),
            ])
            ->emptyStateHeading('Sin alertas activas')
            ->emptyStateDescription('No tienes alertas activas en este momento.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated(false);
    }
}
