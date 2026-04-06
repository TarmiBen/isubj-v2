<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use App\Filament\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Agenda;
use App\Models\User;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class CalendarView extends Page
{
    protected static string $resource = AgendaResource::class;

    protected static string $view = 'filament.resources.agenda-resource.pages.calendar-view';

    protected static ?string $title = 'Calendario de Agendas';

    public $currentMonth;
    public $currentYear;
    public $events = [];
    public $upcomingReservations = [];
    public $pastReservations = [];
    public $selectedAgenda = null;

    public function mount(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->loadReservations();
    }

    public function loadReservations(): void
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->endOfMonth();

        // Query base
        $query = Reservation::with(['user', 'agenda']);

        // Filtrar por agenda si se selecciona
        if ($this->selectedAgenda) {
            $query->where('agenda_id', $this->selectedAgenda);
        }

        // Cargar eventos del mes actual para el calendario
        $monthReservations = (clone $query)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $this->events = $monthReservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'title' => $reservation->user->name . ' - ' . $reservation->agenda->name,
                'start' => $reservation->date->format('Y-m-d') . 'T' . $reservation->start_time,
                'end' => $reservation->date->format('Y-m-d') . 'T' . $reservation->end_time,
                'backgroundColor' => $this->getStatusColor($reservation->status),
                'borderColor' => $this->getStatusColor($reservation->status),
                'extendedProps' => [
                    'status' => $reservation->status,
                    'purpose' => $reservation->purpose,
                    'agenda' => $reservation->agenda->name,
                    'user' => $reservation->user->name,
                ],
            ];
        })->toArray();

        // Cargar reservaciones próximas (próximos 30 días)
        $this->upcomingReservations = (clone $query)
            ->whereDate('date', '>=', now())
            ->whereDate('date', '<=', now()->addDays(30))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Cargar reservaciones pasadas (últimos 30 días)
        $this->pastReservations = (clone $query)
            ->whereDate('date', '>=', now()->subDays(30))
            ->whereDate('date', '<', now())
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->loadReservations();
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->loadReservations();
    }

    public function goToToday(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->loadReservations();
    }

    public function updatedSelectedAgenda(): void
    {
        $this->loadReservations();
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#f59e0b',
            'active' => '#3b82f6',
            'confirmed' => '#10b981',
            'cancelled' => '#6b7280',
            'no_show', 'sanctioned' => '#ef4444',
            default => '#6b7280',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'active' => 'Activo',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            'no_show' => 'No presentado',
            'sanctioned' => 'Sancionado',
            default => $status,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Nueva Reservación')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('agenda_id')
                        ->label('Laboratorio / Agenda')
                        ->options(Agenda::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->live()
                        ->searchable()
                        ->preload()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $agenda = Agenda::find($state);
                                if ($agenda && $agenda->type === 'calendar') {
                                    $set('user_id', null); // Limpiar docente si es calendario
                                }
                            }
                        }),

                    // Campo de docente - SOLO para salas físicas
                    Forms\Components\Select::make('user_id')
                        ->label('Docente')
                        ->options(function() {
                            return User::whereHas('roles', function($query) {
                                $query->where('name', 'teacher');
                            })->pluck('name', 'id');
                        })
                        ->required(function (Get $get) {
                            $agendaId = $get('agenda_id');
                            if (!$agendaId) return false;
                            $agenda = Agenda::find($agendaId);
                            return $agenda && $agenda->type === 'room';
                        })
                        ->visible(function (Get $get) {
                            $agendaId = $get('agenda_id');
                            if (!$agendaId) return true;
                            $agenda = Agenda::find($agendaId);
                            return !$agenda || $agenda->type === 'room';
                        })
                        ->searchable()
                        ->preload(),

                    // Fecha única - SOLO para salas físicas
                    Forms\Components\DatePicker::make('date')
                        ->label('Fecha')
                        ->required(function (Get $get) {
                            $agendaId = $get('agenda_id');
                            if (!$agendaId) return true;
                            $agenda = Agenda::find($agendaId);
                            return !$agenda || $agenda->type === 'room';
                        })
                        ->visible(function (Get $get) {
                            $agendaId = $get('agenda_id');
                            if (!$agendaId) return true;
                            $agenda = Agenda::find($agendaId);
                            return !$agenda || $agenda->type === 'room';
                        })
                        ->minDate(today())
                        ->native(false)
                        ->default(Carbon::create($this->currentYear, $this->currentMonth, 1)),

                    // Rango de fechas - SOLO para calendarios/agendas
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Fecha de inicio')
                                ->required()
                                ->minDate(today())
                                ->native(false)
                                ->live()
                                ->default(Carbon::create($this->currentYear, $this->currentMonth, 1)),
                            Forms\Components\DatePicker::make('end_date')
                                ->label('Fecha de fin')
                                ->required()
                                ->minDate(function (Get $get) {
                                    return $get('start_date') ?: today();
                                })
                                ->native(false),
                        ])
                        ->visible(function (Get $get) {
                            $agendaId = $get('agenda_id');
                            if (!$agendaId) return false;
                            $agenda = Agenda::find($agendaId);
                            return $agenda && $agenda->type === 'calendar';
                        }),

                    Forms\Components\Toggle::make('all_day')
                        ->label('Todo el día')
                        ->inline(false)
                        ->live()
                        ->helperText('El evento abarcará todo el día (00:00 - 23:59)')
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $set('start_time', '00:00');
                                $set('end_time', '23:59');
                            }
                        }),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TimePicker::make('start_time')
                                ->label('Hora de inicio')
                                ->required()
                                ->seconds(false),
                            Forms\Components\TimePicker::make('end_time')
                                ->label('Hora de fin')
                                ->required()
                                ->seconds(false)
                                ->after('start_time'),
                        ])
                        ->visible(function (Get $get) {
                            return !$get('all_day');
                        }),

                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending'    => 'Pendiente',
                            'active'     => 'Activo',
                            'confirmed'  => 'Confirmado',
                        ])
                        ->required()
                        ->default('pending'),

                    Forms\Components\Textarea::make('purpose')
                        ->label('Propósito / Descripción del evento')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $agenda = Agenda::find($data['agenda_id']);

                    // Establecer horario completo si es todo el día
                    if (!empty($data['all_day'])) {
                        $data['start_time'] = '00:00:00';
                        $data['end_time'] = '23:59:59';
                    }

                    // Si es tipo calendario y tiene rango de fechas, crear múltiples reservaciones
                    if ($agenda && $agenda->type === 'calendar' && isset($data['start_date']) && isset($data['end_date'])) {
                        $startDate = Carbon::parse($data['start_date']);
                        $endDate = Carbon::parse($data['end_date']);

                        // Crear una reservación por cada día en el rango
                        $currentDate = $startDate->copy();
                        $created = 0;

                        while ($currentDate <= $endDate) {
                            Reservation::create([
                                'agenda_id' => $data['agenda_id'],
                                'user_id' => null, // Sin docente para calendarios
                                'date' => $currentDate->format('Y-m-d'),
                                'start_time' => $data['start_time'],
                                'end_time' => $data['end_time'],
                                'status' => $data['status'],
                                'purpose' => $data['purpose'],
                                'meta' => [
                                    'is_calendar_event' => true,
                                    'all_day' => !empty($data['all_day']),
                                    'event_range' => [
                                        'start' => $startDate->format('Y-m-d'),
                                        'end' => $endDate->format('Y-m-d'),
                                    ],
                                ],
                            ]);
                            $created++;
                            $currentDate->addDay();
                        }

                        Notification::make()
                            ->title("Evento creado exitosamente ({$created} días)")
                            ->success()
                            ->send();
                    } else {
                        // Crear reservación única para salas físicas
                        Reservation::create([
                            'agenda_id' => $data['agenda_id'],
                            'user_id' => $data['user_id'] ?? null,
                            'date' => $data['date'],
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                            'status' => $data['status'],
                            'purpose' => $data['purpose'],
                            'meta' => [
                                'all_day' => !empty($data['all_day']),
                            ],
                        ]);

                        Notification::make()
                            ->title('Reservación creada exitosamente')
                            ->success()
                            ->send();
                    }

                    $this->loadReservations();
                })
                ->modalWidth('2xl')
                ->slideOver(),
        ];
    }
}

