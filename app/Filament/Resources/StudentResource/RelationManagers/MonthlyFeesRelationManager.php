<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;

class MonthlyFeesRelationManager extends RelationManager
{
    protected static string $relationship = 'monthlyFees';
    protected static ?string $title       = 'Mensualidades';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_label')->label('Período'),
                Tables\Columns\TextColumn::make('config.concept.name')->label('Concepto'),
                Tables\Columns\TextColumn::make('paymentOrder.total')->label('Total')->money('MXN'),
                Tables\Columns\TextColumn::make('paymentOrder.paid_amount')->label('Pagado')->money('MXN'),
                Tables\Columns\TextColumn::make('paymentOrder.balance')->label('Saldo')->money('MXN')
                    ->color(fn ($state) => ($state ?? 0) > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('paymentOrder.due_date')->label('Vence')->date('d/m/Y'),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')
                    ->colors(['warning'=>'pending','success'=>'paid','gray'=>'cancelled'])
                    ->formatStateUsing(fn ($s) => match($s) {
                        'pending'   => 'Pendiente',
                        'paid'      => 'Pagado',
                        'cancelled' => 'Cancelado',
                        default     => $s,
                    }),
            ])
            ->defaultSort('year', 'desc')
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('ver_adeudo')
                    ->label('Ver adeudo')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->url(fn ($record) => $record->payment_order_id
                        ? route('filament.admin.resources.payment-orders.view', $record->payment_order_id)
                        : null
                    )
                    ->visible(fn ($record) => !is_null($record->payment_order_id)),
            ]);
    }
}