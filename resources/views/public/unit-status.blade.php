<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatus de Unidad - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased min-h-screen flex items-center justify-center p-4">

    @php
        $colors = [
            'complete' => ['bg' => 'bg-green-50', 'border' => 'border-green-400', 'text' => 'text-green-800', 'badge' => 'bg-green-600'],
            'partial' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-400', 'text' => 'text-yellow-800', 'badge' => 'bg-yellow-500'],
            'no_grades' => ['bg' => 'bg-red-50', 'border' => 'border-red-400', 'text' => 'text-red-800', 'badge' => 'bg-red-600'],
            'no_document' => ['bg' => 'bg-red-50', 'border' => 'border-red-400', 'text' => 'text-red-800', 'badge' => 'bg-red-600'],
            'no_students' => ['bg' => 'bg-gray-50', 'border' => 'border-gray-400', 'text' => 'text-gray-800', 'badge' => 'bg-gray-500'],
        ];
        $palette = $colors[$status] ?? $colors['no_students'];
    @endphp

    <div class="max-w-xl w-full bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-900 text-white text-center">
            <p class="text-sm uppercase tracking-wide text-gray-300">{{ config('app.name') }}</p>
            <p class="text-xs text-gray-400">Estatus de unidad</p>
        </div>

        <div class="p-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-1">
                {{ $assignment->subject->name ?? 'Materia no disponible' }}
            </h1>
            <h2 class="text-xl font-semibold text-gray-700 mb-4">
                {{ $unit->name }}
            </h2>

            <div class="flex flex-wrap items-center justify-center gap-2 mb-6 text-sm text-gray-600">
                <span class="px-3 py-1 bg-gray-100 rounded-full">
                    Grupo: {{ $assignment->group->code ?? 'N/D' }}
                </span>
                <span class="px-3 py-1 bg-gray-100 rounded-full">
                    Ciclo: {{ $assignment->cycle->name ?? 'N/D' }}
                </span>
                @if($isCurrentCycle)
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                        Ciclo actual
                    </span>
                @else
                    <span class="px-3 py-1 bg-gray-200 text-gray-600 rounded-full font-medium">
                        Ciclo anterior
                    </span>
                @endif
            </div>

            <div class="rounded-xl border-2 {{ $palette['border'] }} {{ $palette['bg'] }} p-6">
                <span class="inline-block px-4 py-1 rounded-full text-white text-xs font-semibold uppercase tracking-wide mb-3 {{ $palette['badge'] }}">
                    Estatus
                </span>
                <p class="text-xl font-bold {{ $palette['text'] }}">
                    {{ $message }}
                </p>
            </div>

            <p class="text-xs text-gray-400 mt-6">
                Aviso Legal: El contenido e información mostrados en esta página son propiedad exclusiva de la institución. Este sitio es de carácter estrictamente informativo; por lo tanto, cualquier uso indebido, reproducción no autorizada o alteración de la información contenida será responsabilidad directa del usuario y podrá ser objeto de las acciones legales y sanciones administrativas correspondientes.
            </p>
        </div>
    </div>

</body>
</html>
