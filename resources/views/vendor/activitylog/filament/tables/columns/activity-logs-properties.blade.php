@php
    // "old"/"attributes" ya vienen alineados: logOnlyDirty() hace que ambos
    // arrays contengan exactamente las mismas llaves (solo los campos que
    // cambiaron), así que no hace falta cruzar listas completas de columnas.
    $properties = collect($getState() ?? []);
    $old = collect($properties->get('old', []));
    $attributes = collect($properties->get('attributes', []));

    // Ruido que no aporta al leer "qué cambió": ya está la fila de
    // activity_log con su propio id/timestamp.
    $noise = ['id', 'created_at', 'updated_at', 'deleted_at'];
    $keys = $attributes->keys()->merge($old->keys())->unique()->diff($noise)->values();

    $format = function ($value) {
        if (is_null($value)) return '—';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        $value = (string) $value;
        return mb_strlen($value) > 60 ? mb_substr($value, 0, 57) . '…' : $value;
    };
@endphp

@if($keys->isEmpty())
    <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
@else
    <div class="flex flex-col gap-1 text-xs">
        @foreach($keys as $key)
            @php
                $hasOld = $old->has($key);
                $hasNew = $attributes->has($key);
                $oldVal = $hasOld ? $old->get($key) : null;
                $newVal = $hasNew ? $attributes->get($key) : null;
            @endphp
            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
                <span class="inline-block rounded bg-gray-500/10 px-1.5 py-0.5 font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                    {{ $key }}
                </span>

                @if($hasOld && $hasNew)
                    <span class="text-danger-600 line-through dark:text-danger-400">{{ $format($oldVal) }}</span>
                    <span aria-hidden="true" class="text-gray-400 dark:text-gray-500">&rarr;</span>
                    <span class="font-semibold text-success-600 dark:text-success-400">{{ $format($newVal) }}</span>
                @elseif($hasNew)
                    <span class="text-gray-700 dark:text-gray-300">{{ $format($newVal) }}</span>
                @else
                    <span class="text-danger-600 line-through dark:text-danger-400">{{ $format($oldVal) }}</span>
                @endif
            </div>
        @endforeach
    </div>
@endif
