<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sistema de Evaluación Docente' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <img class="h-8 w-auto" src="{{ asset('logo.png') }}" alt="Logo">
                        <h1 class="ml-4 text-xl font-semibold text-gray-900">
                            Sistema de Evaluación Docente
                        </h1>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-8">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="text-center text-sm text-gray-500">
                    © {{ date('Y') }} Sistema de Evaluación Docente. Todos los derechos reservados.
                </div>
            </div>
        </footer>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
