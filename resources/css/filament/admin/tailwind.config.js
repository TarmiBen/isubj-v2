import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        // Todo el código Filament del proyecto (recursos, páginas, widgets, helpers).
        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',

        // Todas las vistas Blade propias que se renderizan dentro del panel:
        // vistas de recursos, páginas custom, widgets y la vista de historial de pagos.
        './resources/views/filament/**/*.blade.php',
        './resources/views/components/**/*.blade.php',

        // Vistas del propio Filament (necesario para que su CSS siga completo).
        './vendor/filament/**/*.blade.php',
    ],
}
