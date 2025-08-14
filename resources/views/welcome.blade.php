<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a ISUBJ</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #dbeafe 0%, #ffffff 50%, #dcfce7 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #2563eb, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .icon-bg {
            background: linear-gradient(135deg, #2563eb, #16a34a);
        }
        .btn-teacher {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transition: all 0.2s ease;
        }
        .btn-teacher:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        }
        .btn-admin {
            background: linear-gradient(135deg, #16a34a, #15803d);
            transition: all 0.2s ease;
        }
        .btn-admin:hover {
            background: linear-gradient(135deg, #15803d, #166534);
            box-shadow: 0 10px 25px -5px rgba(22, 163, 74, 0.4);
        }
        .btn-student {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            transition: all 0.2s ease;
        }
        .btn-student:hover {
            background: linear-gradient(135deg, #6d28d9, #5b21b6);
            box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.4);
        }
        .icon-container {
            transition: all 0.3s ease;
        }
        .card-hover:hover .teacher-icon {
            background-color: rgba(59, 130, 246, 0.2);
        }
        .card-hover:hover .admin-icon {
            background-color: rgba(34, 197, 94, 0.2);
        }
        .card-hover:hover .student-icon {
            background-color: rgba(139, 92, 246, 0.2);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-6xl">
    <!-- Header Section -->
    <div class="text-center mb-12">
        <div class="icon-bg inline-flex items-center justify-center w-20 h-20 rounded-full mb-6 shadow-lg">
            <i class="fas fa-book-open text-white text-4xl"></i>
        </div>
        <h1 class="text-5xl font-bold gradient-text mb-4">
            Bienvenido a ISUBJ
        </h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Sistema Integral de Gestión Educativa - Selecciona tu rol para continuar
        </p>
    </div>

    <!-- Cards Section -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
        <!-- Teacher Card -->
        <div class="card-hover bg-white bg-opacity-80 backdrop-filter backdrop-blur-sm rounded-xl shadow-lg border-0 p-8 text-center">
            <div class="teacher-icon icon-container inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-6">
                <i class="fas fa-chalkboard-teacher text-blue-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Portal Docente</h2>
            <p class="text-gray-600 mb-6 leading-relaxed">
                Accede a tus asignaturas, gestiona calificaciones, y lleva un control de tus estudiantes.
            </p>
            <a href="{{ url('/teacher') }}" class="block">
                <button class="btn-teacher w-full text-white font-semibold py-3 px-6 rounded-lg">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    Entrar como Maestro
                </button>
            </a>
            <div class="mt-4 flex items-center justify-center text-sm text-gray-500">
                <i class="fas fa-users mr-1"></i>
                <span>Gestión de Estudiantes</span>
            </div>
        </div>

        <!-- Student Card -->
        <div class="card-hover bg-white bg-opacity-80 backdrop-filter backdrop-blur-sm rounded-xl shadow-lg border-0 p-8 text-center">
            <div class="student-icon icon-container inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-6">
                <i class="fas fa-user-graduate text-purple-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Portal Estudiante</h2>
            <p class="text-gray-600 mb-6 leading-relaxed">
                Consulta tus calificaciones, materias y mantente al día con tus clases.
            </p><br>
            <a href="{{ url('/student') }}" class="block">
                <button class="btn-student w-full text-white font-semibold py-3 px-6 rounded-lg">
                    <i class="fas fa-user-graduate mr-2"></i>
                    Entrar como Estudiante
                </button>
            </a>
            <div class="mt-4 flex items-center justify-center text-sm text-gray-500">
                <i class="fas fa-book mr-1"></i>
                <span>Consulta Académica</span>
            </div>
        </div>

        <!-- Admin Card -->
        <div class="card-hover bg-white bg-opacity-80 backdrop-filter backdrop-blur-sm rounded-xl shadow-lg border-0 p-8 text-center">
            <div class="admin-icon icon-container inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
                <i class="fas fa-cogs text-green-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Panel Administrativo</h2>
            <p class="text-gray-600 mb-6 leading-relaxed">
                Administra el sistema, gestiona usuarios y supervisa el funcionamiento general.
            </p>
            <a href="{{ url('/admin/login') }}" class="block">
                <button class="btn-admin w-full text-white font-semibold py-3 px-6 rounded-lg">
                    <i class="fas fa-cogs mr-2"></i>
                    Entrar como Admin

                </button>
            </a>
            <div class="mt-4 flex items-center justify-center text-sm text-gray-500">
                <i class="fas fa-cogs mr-1"></i>
                <span>Configuración del Sistema</span>
            </div>
        </div>
    </div>

    <!-- Footer -->

</div>
</body>
</html>
