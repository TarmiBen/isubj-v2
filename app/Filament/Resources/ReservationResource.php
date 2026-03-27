<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Filament\Resources\ReservationResource\RelationManagers;
use App\Models\Reservation;
use App\Services\ReservationService;
use Filament\Forms;
use Filament\Forms\Form;
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
                    ->searchable(),
                Forms\Components\Select::make('user_id')
                    ->label('Docente')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->minDate(today())
                    ->native(false),
                Forms\Components\TimePicker::make('start_time')
                    ->label('Hora de inicio')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Hora de fin')
                    ->required()
                    ->seconds(false)
                    ->after('start_time'),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('agenda.name')
                    ->label('Agenda')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Inicio')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Fin')
                    ->time('H:i'),
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
        ];
    }
}
