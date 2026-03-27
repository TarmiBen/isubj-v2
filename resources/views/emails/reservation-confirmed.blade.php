<x-mail::message>
# Confirmación de Reservación

Hola {{ $reservation->user->name }},

Tu reservación ha sido confirmada exitosamente:

**Agenda:** {{ $reservation->agenda->name }}
**Fecha:** {{ $reservation->date->format('d/m/Y') }}
**Hora de inicio:** {{ date('H:i', strtotime($reservation->start_time)) }}
**Hora de fin:** {{ date('H:i', strtotime($reservation->end_time)) }}

@if($reservation->purpose)
**Propósito:** {{ $reservation->purpose }}
@endif

@if($reservation->agenda->hasPhysicalQr())
<x-mail::panel>
⚠️ **Importante:** Este espacio requiere escaneo de QR al entrar y salir.
Por favor, llega a tiempo y recuerda escanear el código QR al finalizar tu sesión.
</x-mail::panel>
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
