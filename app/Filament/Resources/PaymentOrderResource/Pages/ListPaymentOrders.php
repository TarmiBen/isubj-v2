<?php

namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListPaymentOrders extends ListRecords
{
    protected static string $resource = PaymentOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('generateMonthlyFees')
                    ->label('Generar mensualidades del mes')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->visible(fn () => auth()->user()->can('create_payment::order'))
                    ->action(function () {
                        Artisan::call('payments:generate-monthly-fees', [
                            '--month' => now()->month,
                            '--year'  => now()->year,
                        ]);
                        $output = trim(Artisan::output());

                        $summary = collect(explode("\n", $output))
                            ->first(fn ($line) => str_contains($line, 'Resultado:')) ?? $output;

                        Notification::make()
                            ->title('Generación de mensualidades completada')
                            ->body($summary)
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Acciones')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button()
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
