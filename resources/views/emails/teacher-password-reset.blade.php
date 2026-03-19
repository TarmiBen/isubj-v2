<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecimiento de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Restablecimiento de Contraseña</h1>
        <p style="color: white; margin: 10px 0 0 0; opacity: 0.9;">Panel de Profesores</p>
    </div>

    <div style="padding: 0 20px;">
        <p style="font-size: 16px; margin-bottom: 20px;">
            Hola <strong>{{ $user->name }}</strong>,
        </p>

        <p style="margin-bottom: 20px;">
            Has solicitado restablecer tu contraseña para el panel de profesores. Para continuar con el proceso, haz clic en el siguiente botón:
        </p>

        <div style="text-align: center; margin: 40px 0;">
            <a href="{{ route('filament.teacher.auth.password-reset.reset', ['token' => $token, 'email' => $user->email]) }}"
               style="background-color: #f59e0b; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                Restablecer mi Contraseña
            </a>
        </div>

        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 30px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;">
                <strong>⚠️ Importante:</strong> Este enlace expirará en <strong>60 minutos</strong> por razones de seguridad.
            </p>
        </div>

        <p style="margin-bottom: 20px; font-size: 14px; color: #666;">
            Si no solicitaste este restablecimiento de contraseña, puedes ignorar este correo de forma segura. Tu contraseña no será modificada.
        </p>
    </div>

    <hr style="margin: 40px 20px; border: none; border-top: 1px solid #e5e7eb;">

    <div style="padding: 0 20px;">
        <p style="font-size: 12px; color: #9ca3af; line-height: 1.4;">
            <strong>¿Problemas con el botón?</strong><br>
            Si tienes dificultades haciendo clic en el botón de arriba, copia y pega la siguiente URL en tu navegador:
        </p>
        <p style="font-size: 11px; color: #6b7280; word-break: break-all; background-color: #f9fafb; padding: 10px; border-radius: 4px; margin: 10px 0;">
            {{ route('filament.teacher.auth.password-reset.reset', ['token' => $token, 'email' => $user->email]) }}
        </p>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p style="font-size: 12px; color: #9ca3af; margin: 0;">
                Saludos,<br>
                <strong>Sistema de Gestión Académica</strong>
            </p>
        </div>
    </div>
</body>
</html>
