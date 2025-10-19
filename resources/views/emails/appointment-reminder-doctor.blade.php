<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recordatorio de Consulta Médica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2196F3;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .appointment-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .meeting-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .urgent {
            background-color: #ffebee;
            border-left-color: #f44336;
        }
        .button {
            display: inline-block;
            background-color: #2196F3;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>👨‍⚕️ Recordatorio de Consulta Médica</h1>
        <p>SaludOne - Sistema de Gestión Médica</p>
    </div>

    <div class="content">
        <h2>Hola Dr. {{ $doctor->name }},</h2>

        <p>Te recordamos que tienes una consulta médica {{ $reminder_type === '24h' ? 'mañana' : ($reminder_type === '2h' ? 'en 2 horas' : 'en 30 minutos') }}:</p>

        <div class="appointment-details {{ $appointment->urgent ? 'urgent' : '' }}">
            <h3>📅 Detalles de la Consulta</h3>
            <p><strong>Fecha y Hora:</strong> {{ $appointment->start_date->format('d/m/Y H:i') }}</p>
            <p><strong>Paciente:</strong> {{ $patient->name }}</p>
            <p><strong>Email del Paciente:</strong> {{ $patient->email }}</p>
            <p><strong>Especialidad:</strong> {{ $specialty->name }}</p>
            <p><strong>Tipo:</strong> {{ ucfirst($appointment->type) }}</p>
            <p><strong>Motivo:</strong> {{ $appointment->reason }}</p>

            @if($appointment->urgent)
                <p style="color: #f44336; font-weight: bold;">⚠️ CONSULTA URGENTE</p>
            @endif
        </div>

        @if($appointment->isVirtual() && $meeting_link)
            <div class="meeting-info">
                <h3>💻 Información de Teleconsulta</h3>
                <p><strong>Enlace de la reunión:</strong></p>
                <a href="{{ $meeting_link }}" class="button">Iniciar Teleconsulta</a>

                @if($meeting_password)
                    <p><strong>Contraseña:</strong> {{ $meeting_password }}</p>
                @endif

                <p><em>Por favor, inicia la reunión 5 minutos antes de la hora programada.</em></p>
            </div>
        @endif

        <div style="margin: 20px 0;">
            <h3>📋 Preparación para la Consulta:</h3>
            <ul>
                @if($appointment->type === 'presencial')
                    <li>Revisa el historial médico del paciente</li>
                    <li>Prepara el consultorio con los equipos necesarios</li>
                    <li>Verifica que todos los documentos estén disponibles</li>
                @else
                    <li>Verifica que tu conexión a internet sea estable</li>
                    <li>Prepara un espacio profesional y bien iluminado</li>
                    <li>Revisa el historial médico del paciente</li>
                    <li>Ten a la mano los formularios y documentos necesarios</li>
                @endif
            </ul>
        </div>

        <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4>📝 Notas Importantes:</h4>
            <p>Recuerda documentar adecuadamente la consulta y generar el registro médico correspondiente al finalizar la cita.</p>
        </div>

        <p>¡Que tengas una excelente consulta!</p>
    </div>

    <div class="footer">
        <p>Este es un mensaje automático del sistema SaludOne.</p>
        <p>Para más información, contacta con nuestro equipo de soporte.</p>
    </div>
</body>
</html>
