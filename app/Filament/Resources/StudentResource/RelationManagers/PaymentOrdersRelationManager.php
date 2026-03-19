<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\PaymentConcept;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentOrders';
    protected static ?string $title       = 'Adeudos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('payment_concept_id')
                ->label('Concepto')->required()
                ->options(PaymentConcept::active()->pluck('name', 'id')),
            Forms\Components\TextInput::make('subtotal')->label('Subtotal')->required()->numeric()->prefix('$'),
            Forms\Components\TextInput::make('discount_amount')->label('Descuento')->numeric()->prefix('$')->default(0),
            Forms\Components\TextInput::make('tax_amount')->label('IVA')->numeric()->prefix('$')->default(0),
            Forms\Components\DatePicker::make('due_date')->label('Fecha vencimiento')->required(),
            Forms\Components\DatePicker::make('period_start')->label('Período inicio'),
            Forms\Components\DatePicker::make('period_end')->label('Período fin'),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio'),
                Tables\Columns\TextColumn::make('concept.name')->label('Concepto'),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('MXN'),
                Tables\Columns\TextColumn::make('paid_amount')->label('Pagado')->money('MXN'),
                Tables\Columns\TextColumn::make('balance')->label('Saldo')->money('MXN')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('due_date')->label('Vence')->date('d/m/Y'),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'partial',
                        'success' => 'paid',
                        'danger'  => 'overdue',
                        'gray'    => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($s) => match($s) {
                        'pending'      => 'Pendiente',
                        'partial'      => 'Parcial',
                        'paid'         => 'Pagado',
                        'overdue'      => 'Vencido',
                        'cancelled'    => 'Cancelado',
                        'in_agreement' => 'En convenio',
                        default        => $s,
                    }),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')
                    ->options(['pending'=>'Pendiente','partial'=>'Parcial','paid'=>'Pagado','overdue'=>'Vencido']),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar adeudo')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['created_by']    = auth()->id();
                        $subtotal = (float) ($data['subtotal'] ?? 0);
                        $discount = (float) ($data['discount_amount'] ?? 0);
                        $tax      = (float) ($data['tax_amount'] ?? 0);
                        $total    = max(0, $subtotal - $discount + $tax);
                        $data['total']       = $total;
                        $data['paid_amount'] = 0;
                        $data['balance']     = $total;
                        $data['status']      = 'pending';
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }
}