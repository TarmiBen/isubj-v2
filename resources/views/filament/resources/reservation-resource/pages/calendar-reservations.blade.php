<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Controles de navegación del calendario --}}
        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center gap-4">
                <x-filament::button wire:click="previousMonth" icon="heroicon-o-chevron-left" size="sm">
                    Anterior
                </x-filament::button>

                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('es')->isoFormat('MMMM YYYY') }}
                </h2>

                <x-filament::button wire:click="nextMonth" icon="heroicon-o-chevron-right" size="sm">
                    Siguiente
                </x-filament::button>
            </div>

            <x-filament::button wire:click="goToToday" color="gray" size="sm">
                Hoy
            </x-filament::button>
        </div>

        {{-- Calendario --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            {{-- Leyenda de estados --}}
            <div class="mb-4 flex flex-wrap gap-3 justify-center text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background-color: #f59e0b;"></div>
                    <span class="text-gray-700 dark:text-gray-300">Pendiente</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background-color: #3b82f6;"></div>
                    <span class="text-gray-700 dark:text-gray-300">Activo</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background-color: #10b981;"></div>
                    <span class="text-gray-700 dark:text-gray-300">Confirmado</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background-color: #6b7280;"></div>
                    <span class="text-gray-700 dark:text-gray-300">Cancelado</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background-color: #ef4444;"></div>
                    <span class="text-gray-700 dark:text-gray-300">No presentado / Sancionado</span>
                </div>
            </div>

            <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    💡 <strong>Tip:</strong> Haz clic en un evento para ver sus detalles. Haz clic en un día vacío para crear una nueva reservación.
                </p>
            </div>

            <div id="calendar" wire:ignore></div>
        </div>

        {{-- Listas de reservaciones --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Próximas reservaciones (30 días) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Próximas Reservaciones (30 días)
                    </h3>
                </div>
                <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                    @forelse($upcomingReservations as $reservation)
                        <a href="{{ \App\Filament\Resources\ReservationResource::getUrl('view', ['record' => $reservation->id]) }}"
                           class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $reservation->user->name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $reservation->agenda->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $reservation->date->format('d/m/Y') }} -
                                        {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} a
                                        {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                                    </p>
                                </div>
                                <x-filament::badge :color="match($reservation->status) {
                                    'pending' => 'warning',
                                    'active' => 'primary',
                                    'confirmed' => 'success',
                                    'cancelled' => 'gray',
                                    'no_show', 'sanctioned' => 'danger',
                                    default => 'gray',
                                }">
                                    {{ $this->getStatusLabel($reservation->status) }}
                                </x-filament::badge>
                            </div>
                        </a>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                            No hay reservaciones próximas
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Reservaciones pasadas (30 días) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Reservaciones Recientes (30 días)
                    </h3>
                </div>
                <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                    @forelse($pastReservations as $reservation)
                        <a href="{{ \App\Filament\Resources\ReservationResource::getUrl('view', ['record' => $reservation->id]) }}"
                           class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $reservation->user->name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $reservation->agenda->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $reservation->date->format('d/m/Y') }} -
                                        {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} a
                                        {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                                    </p>
                                </div>
                                <x-filament::badge :color="match($reservation->status) {
                                    'pending' => 'warning',
                                    'active' => 'primary',
                                    'confirmed' => 'success',
                                    'cancelled' => 'gray',
                                    'no_show', 'sanctioned' => 'danger',
                                    default => 'gray',
                                }">
                                    {{ $this->getStatusLabel($reservation->status) }}
                                </x-filament::badge>
                            </div>
                        </a>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                            No hay reservaciones recientes
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/es.global.min.js'></script>
    <script>
        let calendar = null;

        function initCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;

            if (calendar) {
                calendar.destroy();
            }

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: false,
                height: 'auto',
                events: @this.events,
                selectable: true,
                selectMirror: true,
                eventClick: function(info) {
                    window.location.href = '/admin/reservations/' + info.event.id;
                },
                dateClick: function(info) {
                    // Abrir el modal de crear con la fecha pre-seleccionada
                    const createButton = document.querySelector('[wire\\:click="mountAction(\'create\')"]');
                    if (createButton) {
                        createButton.click();
                        // Esperar un momento y luego establecer la fecha
                        setTimeout(() => {
                            const dateInput = document.querySelector('input[name="data.date"]');
                            if (dateInput) {
                                dateInput.value = info.dateStr;
                                dateInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }, 300);
                    }
                },
                eventContent: function(arg) {
                    return {
                        html: '<div class="fc-event-main-frame"><div class="fc-event-time">' +
                              arg.timeText + '</div><div class="fc-event-title-container"><div class="fc-event-title fc-sticky">' +
                              arg.event.title + '</div></div></div>'
                    };
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día'
                },
                initialDate: new Date(@js($currentYear), @js($currentMonth - 1), 1),
                firstDay: 1,
                dayMaxEvents: true,
            });

            calendar.render();
        }

        document.addEventListener('DOMContentLoaded', function() {
            initCalendar();
        });

        // Reinicializar el calendario cuando Livewire actualiza
        document.addEventListener('livewire:navigated', () => {
            setTimeout(() => {
                initCalendar();
            }, 100);
        });

        // Escuchar eventos de Livewire
        Livewire.hook('morph.updated', () => {
            setTimeout(() => {
                initCalendar();
            }, 100);
        });
    </script>

    <style>
        .fc .fc-toolbar.fc-header-toolbar {
            display: none;
        }

        .fc-event {
            cursor: pointer;
            font-size: 0.75rem;
            margin-bottom: 2px;
        }

        .fc-daygrid-event {
            white-space: normal;
            padding: 2px 4px;
        }

        .fc-event-title {
            font-weight: 500;
        }

        .fc-event-time {
            font-weight: 600;
        }

        .fc .fc-daygrid-day-number {
            padding: 4px;
            font-size: 0.875rem;
        }

        .fc .fc-col-header-cell-cushion {
            padding: 8px 4px;
        }

        .fc-daygrid-day:hover {
            background-color: rgba(59, 130, 246, 0.1);
            cursor: pointer;
        }

        .fc-day-today {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }

        .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            background-color: #3b82f6;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fc-event:hover {
            opacity: 0.85;
        }

        .fc .fc-daygrid-body-unbalanced .fc-daygrid-day-events {
            min-height: 2em;
        }

        .fc .fc-more-link {
            color: #3b82f6;
            font-weight: 600;
        }
    </style>
    @endpush
</x-filament-panels::page>

