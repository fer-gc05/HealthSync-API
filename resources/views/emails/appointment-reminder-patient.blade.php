<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recordatorio de Cita Médica</title>
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
            background-color: #4CAF50;
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
            border-left: 4px solid #4CAF50;
        }
        .meeting-info {
            background-color: #e8f5e8;
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
            background-color: #4CAF50;
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
        <h1>🏥 Recordatorio de Cita Médica</h1>
        <p>SaludOne - Sistema de Gestión Médica</p>
    </div>

    <div class="content">
        <h2>Hola {{ $patient->name }},</h2>

        <p>Te recordamos que tienes una cita médica {{ $reminder_type === '24h' ? 'mañana' : ($reminder_type === '2h' ? 'en 2 horas' : 'en 30 minutos') }}:</p>

        <div class="appointment-details {{ $appointment->urgent ? 'urgent' : '' }}">
            <h3>📅 Detalles de la Cita</h3>
            <p><strong>Fecha y Hora:</strong> {{ $appointment->start_date->format('d/m/Y H:i') }}</p>
            <p><strong>Doctor:</strong> {{ $doctor->name }}</p>
            <p><strong>Especialidad:</strong> {{ $specialty->name }}</p>
            <p><strong>Tipo:</strong> {{ ucfirst($appointment->type) }}</p>
            <p><strong>Motivo:</strong> {{ $appointment->reason }}</p>

            @if($appointment->urgent)
                <p style="color: #f44336; font-weight: bold;">⚠️ CITA URGENTE</p>
            @endif
        </div>

        @if($appointment->isVirtual() && $meeting_link)
            <div class="meeting-info">
                <h3>💻 Información de Teleconsulta</h3>
                <p><strong>Enlace de la reunión:</strong></p>
                <a href="{{ $meeting_link }}" class="button">Unirse a la Teleconsulta</a>

                @if($meeting_password)
                    <p><strong>Contraseña:</strong> {{ $meeting_password }}</p>
                @endif

                <p><em>Por favor, únete a la reunión 5 minutos antes de la hora programada.</em></p>
            </div>
        @endif

        <div style="margin: 20px 0;">
            <h3>📋 Instrucciones Importantes:</h3>
            <ul>
                @if($appointment->type === 'presencial')
                    <li>Llega 15 minutos antes de tu cita</li>
                    <li>Trae tu identificación y documentos médicos relevantes</li>
                    <li>Si tienes síntomas de COVID-19, notifica al personal</li>
                @else
                    <li>Verifica que tu conexión a internet sea estable</li>
                    <li>Usa un dispositivo con cámara y micrófono</li>
                    <li>Busca un lugar tranquilo y bien iluminado</li>
                    <li>Ten a la mano tus documentos médicos</li>
                @endif
            </ul>
        </div>

        <p>Si necesitas reprogramar o cancelar tu cita, por favor contacta con nosotros lo antes posible.</p>

        <p>¡Esperamos verte pronto!</p>
    </div>

    <div class="footer">
        <p>Este es un mensaje automático del sistema SaludOne.</p>
        <p>Para más información, contacta con nuestro equipo de soporte.</p>
    </div>
</body>
</html>
