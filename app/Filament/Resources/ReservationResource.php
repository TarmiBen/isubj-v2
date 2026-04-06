<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Filament\Resources\ReservationResource\RelationManagers;
use App\Models\Reservation;
use App\Services\ReservationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Agendas';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Reservaciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('agenda_id')
                    ->label('Laboratorio / Agenda')
                    ->relationship('agenda', 'name')
                    ->required()
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state) {
                            $agenda = \App\Models\Agenda::find($state);
                            if ($agenda && $agenda->type === 'calendar') {
                                $set('user_id', null);
                            }
                        }
                    }),

                // Campo de docente - SOLO para salas físicas
                Forms\Components\Select::make('user_id')
                    ->label('Docente')
                    ->relationship('user', 'name')
                    ->required(function (Get $get) {
                        $agendaId = $get('agenda_id');
                        if (!$agendaId) return false;
                        $agenda = \App\Models\Agenda::find($agendaId);
                        return $agenda && $agenda->type === 'room';
                    })
                    ->visible(function (Get $get) {
                        $agendaId = $get('agenda_id');
                        if (!$agendaId) return true;
                        $agenda = \App\Models\Agenda::find($agendaId);
                        return !$agenda || $agenda->type === 'room';
                    })
                    ->searchable()
                    ->helperText('Solo para salas físicas con docente asignado'),

                // Fecha única - SOLO para salas físicas
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required(function (Get $get) {
                        $agendaId = $get('agenda_id');
                        if (!$agendaId) return true;
                        $agenda = \App\Models\Agenda::find($agendaId);
                        return !$agenda || $agenda->type === 'room';
                    })
                    ->visible(function (Get $get) {
                        $agendaId = $get('agenda_id');
                        if (!$agendaId) return true;
                        $agenda = \App\Models\Agenda::find($agendaId);
                        return !$agenda || $agenda->type === 'room';
                    })
                    ->minDate(today())
                    ->native(false),

                // Rango de fechas - SOLO para calendarios/agendas
                Forms\Components\Section::make('Rango de fechas del evento')
                    ->description('Este evento abarcará varios días')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Fecha de inicio')
                                    ->required()
                                    ->minDate(today())
                                    ->native(false)
                                    ->live(),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fecha de fin')
                                    ->required()
                                    ->minDate(function (Get $get) {
                                        return $get('start_date') ?: today();
                                    })
                                    ->native(false),
                            ]),
                    ])
                    ->visible(function (Get $get) {
                        $agendaId = $get('agenda_id');
                        if (!$agendaId) return false;
                        $agenda = \App\Models\Agenda::find($agendaId);
                        return $agenda && $agenda->type === 'calendar';
                    })
                    ->collapsible(),

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

                Forms\Components\TimePicker::make('start_time')
                    ->label('Hora de inicio')
                    ->required()
                    ->seconds(false)
                    ->visible(fn (Get $get) => !$get('all_day'))
                    ->dehydrated(),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Hora de fin')
                    ->required()
                    ->seconds(false)
                    ->after('start_time')
                    ->visible(fn (Get $get) => !$get('all_day'))
                    ->dehydrated(),
                Forms\Components\Textarea::make('purpose')
                    ->label('Propósito de la sesión')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending'    => 'Pendiente',
                        'active'     => 'Activo',
                        'confirmed'  => 'Confirmado',
                        'cancelled'  => 'Cancelado',
                        'no_show'    => 'No presentado',
                        'sanctioned' => 'Sancionado',
                    ])
                    ->required()
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Docente')
                    ->searchable()
                    ->sortable()
                    ->default('Evento de Agenda')
                    ->description(fn ($record) => $record->user_id ? null : '📅 Evento de calendario'),
                Tables\Columns\TextColumn::make('agenda.name')
                    ->label('Agenda')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->agenda->type === 'calendar' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(function ($record) {
                        if (!empty($record->meta['is_calendar_event']) && !empty($record->meta['event_range'])) {
                            $start = \Carbon\Carbon::parse($record->meta['event_range']['start'])->format('d/m/Y');
                            $end = \Carbon\Carbon::parse($record->meta['event_range']['end'])->format('d/m/Y');
                            return "Evento: {$start} - {$end}";
                        }
                        return null;
                    }),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Inicio')
                    ->formatStateUsing(function ($state, $record) {
                        if (!empty($record->meta['all_day'])) {
                            return 'Todo el día';
                        }
                        return \Carbon\Carbon::parse($state)->format('H:i');
                    })
                    ->badge()
                    ->color(fn ($record) => !empty($record->meta['all_day']) ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Fin')
                    ->formatStateUsing(function ($state, $record) {
                        if (!empty($record->meta['all_day'])) {
                            return '';
                        }
                        return \Carbon\Carbon::parse($state)->format('H:i');
                    })
                    ->default('-'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Pendiente',
                            'active' => 'Activo',
                            'confirmed' => 'Confirmado',
                            'cancelled' => 'Cancelado',
                            'no_show' => 'No presentado',
                            'sanctioned' => 'Sancionado',
                            default => $state,
                        };
                    })
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'active',
                        'success' => 'confirmed',
                        'gray' => 'cancelled',
                        'danger' => fn($state) => in_array($state, ['no_show', 'sanctioned']),
                    ]),
                Tables\Columns\IconColumn::make('meta.check_in.at')
                    ->label('Check-in')
                    ->boolean()
                    ->getStateUsing(fn($record) => !empty($record->meta['check_in'])),
                Tables\Columns\IconColumn::make('meta.check_out.at')
                    ->label('Check-out')
                    ->boolean()
                    ->getStateUsing(fn($record) => !empty($record->meta['check_out'])),
                Tables\Columns\IconColumn::make('sancion')
                    ->label('Sanción')
                    ->getStateUsing(fn($record) => $record->hasSanction())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('agenda')
                    ->relationship('agenda', 'name')
                    ->label('Agenda'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending'    => 'Pendiente',
                        'active'     => 'Activo',
                        'confirmed'  => 'Confirmado',
                        'cancelled'  => 'Cancelado',
                        'no_show'    => 'No presentado',
                        'sanctioned' => 'Sancionado',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'active']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo')
                            ->required(),
                    ])
                    ->action(function ($record, $data) {
                        app(ReservationService::class)->cancel($record, auth()->user(), $data['reason']);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
            'view' => Pages\ViewReservation::route('/{record}'),
            'calendar' => Pages\CalendarReservations::route('/calendar'),
        ];
    }
}
