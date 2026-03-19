<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title       = 'Pagos';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio'),
                Tables\Columns\TextColumn::make('method.name')->label('Método'),
                Tables\Columns\TextColumn::make('amount_received')->label('Recibido')->money('MXN'),
                Tables\Columns\TextColumn::make('amount_applied')->label('Aplicado')->money('MXN'),
                Tables\Columns\TextColumn::make('payment_date')->label('Fecha')->dateTime('d/m/Y H:i'),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')
                    ->colors(['success'=>'applied','warning'=>'partial','gray'=>'pending','danger'=>'cancelled'])
                    ->formatStateUsing(fn ($s) => match($s) {
                        'applied'   => 'Aplicado',
                        'partial'   => 'Parcial',
                        'pending'   => 'Pendiente',
                        'cancelled' => 'Cancelado',
                        default     => $s,
                    }),
                Tables\Columns\TextColumn::make('receivedBy.name')->label('Cajero'),
            ])
            ->defaultSort('payment_date', 'desc')
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}