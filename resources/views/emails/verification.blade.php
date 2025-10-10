<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Email -SaludOne </title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .title {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .verification-code {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }
        .expires {
            color: #6b7280;
            font-size: 14px;
            margin-top: 10px;
        }
        .instructions {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 10px 0;
        }
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            .code {
                font-size: 24px;
                letter-spacing: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏥 SaludOne</div>
            <h1 class="title">Verifica tu cuenta</h1>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $userName }}</strong>,</p>
            
            <p>Gracias por registrarte en SaludOne. Para completar tu registro y acceder a todas las funcionalidades, necesitas verificar tu dirección de correo electrónico.</p>

            <div class="verification-code">
                <div class="code">{{ $code }}</div>
                <div class="expires">Este código expira en {{ $expiresIn }}</div>
            </div>

            <div class="instructions">
                <strong>📋 Instrucciones:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Copia el código de 6 dígitos mostrado arriba</li>
                    <li>Ve a la aplicación SaludOne</li>
                    <li>Ingresa el código en el campo de verificación</li>
                    <li>¡Listo! Tu cuenta estará verificada</li>
                </ul>
            </div>

            <p>Si no solicitaste este código, puedes ignorar este email de forma segura.</p>
        </div>

        <div class="footer">
            <p>Este email fue enviado automáticamente por SaludOne.</p>
            <p>© {{ date('Y') }} SaludOne. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
