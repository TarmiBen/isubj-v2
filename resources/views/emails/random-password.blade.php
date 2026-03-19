<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva Contraseña Generada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Nueva Contraseña Generada</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Sistema de Gestión</p>
    </div>

    <div style="padding: 0 20px;">
        <p style="font-size: 16px; margin-bottom: 20px;">
            Hola <strong>{{ $user->name }}</strong>,
        </p>

        <p style="margin-bottom: 20px;">
            Se ha generado una nueva contraseña aleatoria para tu cuenta en el sistema.
        </p>

        <div style="background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: center;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #374151;">Tu nueva contraseña temporal es:</p>
            <div style="background-color: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 18px; font-weight: bold; letter-spacing: 2px; margin: 15px 0;">
                {{ $password }}
            </div>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #6b7280;">
                <strong>Importante:</strong> Copia esta contraseña y guárdala en un lugar seguro.
            </p>
        </div>

        <div style="background-color: #fef3cd; border: 1px solid #fbbf24; border-radius: 8px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                <strong>⚠️ Recomendación de Seguridad:</strong><br>
                Te recomendamos cambiar esta contraseña por una de tu elección después de iniciar sesión.
            </p>
        </div>

        <p style="margin-bottom: 20px;">
            Para acceder al sistema, utiliza tu correo electrónico y la contraseña proporcionada arriba.
        </p>

        @php
            $loginUrl = '';
            // Determinar la URL de login basándose en el tipo de usuario
            if ($user->userable_type === 'App\\Models\\Teacher') {
                $loginUrl = route('filament.teacher.auth.login');
            } else {
                try {
                    $loginUrl = route('filament.admin.auth.login');
                } catch (\Exception $e) {
                    // Si no existe el panel admin, usar el de teacher como fallback
                    $loginUrl = route('filament.teacher.auth.login');
                }
            }
        @endphp

        <div style="text-align: center; margin: 40px 0;">
            <a href="{{ $loginUrl }}"
               style="background-color: #10b981; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                Iniciar Sesión
            </a>
        </div>

        <div style="border-top: 1px solid #e5e7eb; margin-top: 40px; padding-top: 20px;">
            <h3 style="color: #374151; margin-bottom: 15px;">Información de tu cuenta:</h3>
            <ul style="color: #6b7280; margin: 0; padding-left: 20px;">
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>Nueva contraseña:</strong> {{ $password }}</li>
            </ul>
        </div>

        <p style="font-size: 14px; color: #6b7280; margin-top: 30px; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            Si no solicitaste este cambio de contraseña, por favor contacta al administrador del sistema inmediatamente.
        </p>
    </div>

    <div style="text-align: center; margin-top: 40px; padding: 20px; background-color: #f9fafb; border-radius: 8px;">
        <p style="margin: 0; color: #6b7280; font-size: 12px;">
            Este correo fue enviado automáticamente por el Sistema de Gestión.<br>
            Por favor, no respondas a este correo.
        </p>
    </div>
</body>
</html>
