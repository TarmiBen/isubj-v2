<?php

namespace App\Filament\Resources\AgendaResource\RelationManagers;

use App\Models\Reservation;
use App\Services\ReservationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    protected static ?string $title = 'Reservaciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')
                    ->label('Usuario')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Docente')
                    ->searchable()
                    ->sortable(),
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
                        'pending' => 'Pendiente',
                        'active' => 'Activo',
                        'confirmed' => 'Confirmado',
                        'cancelled' => 'Cancelado',
                        'no_show' => 'No presentado',
                        'sanctioned' => 'Sancionado',
                    ]),
            ])
            ->headerActions([
                // No permitir crear desde aquí
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
                // Sin acciones en masa
            ])
            ->defaultSort('date', 'desc');
    }
}

