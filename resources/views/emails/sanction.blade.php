<x-mail::message>
# Notificación de Sanción

Hola {{ $reservation->user->name }},

@if($type === 'no_show')
Se ha detectado que **no te presentaste** a tu reservación programada:
@else
Se ha detectado que **no registraste tu salida** de la sesión:
@endif

**Agenda:** {{ $reservation->agenda->name }}
**Fecha:** {{ $reservation->date->format('d/m/Y') }}
**Hora programada:** {{ date('H:i', strtotime($reservation->start_time)) }} - {{ date('H:i', strtotime($reservation->end_time)) }}

<x-mail::panel>
❌ Esta incidencia ha sido registrada en el sistema.

@if($type === 'no_show')
Recuerda que es importante presentarte a tus reservaciones o cancelarlas con anticipación.
@else
Por favor, no olvides escanear el código QR de salida al terminar tu sesión en el futuro.
@endif
</x-mail::panel>

Si tienes alguna duda o consideras que esto es un error, por favor contacta al administrador del sistema.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
