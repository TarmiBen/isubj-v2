<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $survey->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            white-space: pre-line;
            margin-bottom: 30px;
        }
        .access-info {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            margin: 20px 0;
        }
        .access-info h3 {
            color: #1976d2;
            margin-top: 0;
        }
        .code {
            font-size: 18px;
            font-weight: bold;
            color: #d32f2f;
            background-color: #fff3e0;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .important-note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ $survey->name }}</h1>
        </div>

        <div class="content">
            {{ $emailBody }}
        </div>

        <div class="access-info">
            <h3>🔑 Información de Acceso</h3>
            <p><strong>Tu código de estudiante:</strong></p>
            <div class="code">{{ $studentCode }}</div>
            <p><strong>Enlace directo a la evaluación:</strong></p>
            <p><a href="{{ $surveyUrl }}" class="button">Acceder a la Evaluación</a></p>
        </div>

        <div class="important-note">
            <p><strong>📌 Instrucciones:</strong></p>
            <ul>
                <li>Puedes usar tu código de estudiante o hacer clic en el enlace directo</li>
                <li>La evaluación se guarda automáticamente mientras respondes</li>
                <li>Puedes completarla en varias sesiones si es necesario</li>
                <li>Recuerda evaluar todas las asignaturas de tu grupo</li>
            </ul>
        </div>

        <div class="footer">
            <p>Este es un correo automático, por favor no responder.</p>
            <p>Si tienes problemas técnicos, contacta al soporte académico.</p>
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            <p><small>{{ $survey->description }}</small></p>
        </div>
    </div>
</body>
</html>
