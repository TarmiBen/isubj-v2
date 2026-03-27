<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\ReservationResource\Pages;
use App\Filament\Teacher\Resources\ReservationResource\RelationManagers;
use App\Models\Reservation;
use App\Models\Agenda;
use App\Services\ReservationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Mis Reservaciones';

    protected static ?int $navigationSort = 3;

    // Solo mostrar reservaciones del usuario autenticado
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('agenda_id')
                    ->label('Laboratorio / Agenda')
                    ->options(Agenda::active()->pluck('name', 'id'))
                    ->required()
                    ->live()
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
                    ->seconds(false),
                Forms\Components\Textarea::make('purpose')
                    ->label('Propósito de la sesión')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                Tables\Columns\IconColumn::make('check_in')
                    ->label('Check-in')
                    ->getStateUsing(fn($record) => !empty($record->meta['check_in']))
                    ->boolean(),
                Tables\Columns\IconColumn::make('check_out')
                    ->label('Check-out')
                    ->getStateUsing(fn($record) => !empty($record->meta['check_out']))
                    ->boolean(),
            ])
            ->filters([
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
                // Sin bulk actions para teachers
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
            'view' => Pages\ViewReservation::route('/{record}'),
        ];
    }

    // Teachers no pueden editar ni eliminar
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
