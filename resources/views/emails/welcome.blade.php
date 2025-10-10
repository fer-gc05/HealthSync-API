<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido a SaludOne!</title>
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
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .title {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .welcome-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .features {
            margin: 30px 0;
        }
        .feature {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }
        .feature-icon {
            font-size: 24px;
            margin-right: 15px;
        }
        .feature-text {
            flex: 1;
        }
        .feature-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .feature-description {
            color: #6b7280;
            font-size: 14px;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f0f9ff;
            border-radius: 8px;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin: 10px 0;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 10px;
        }
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            .feature {
                flex-direction: column;
                text-align: center;
            }
            .feature-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏥 SaludOne</div>
            <h1 class="title">¡Bienvenido a SaludOne!</h1>
        </div>

        <div class="welcome-message">
            <h2 style="margin: 0 0 10px 0; font-size: 24px;">¡Hola {{ $userName }}!</h2>
            <p style="margin: 0; font-size: 16px;">Tu cuenta ha sido creada exitosamente y ya puedes comenzar a usar SaludOne.</p>
        </div>

        <div class="content">
            <p>Estamos emocionados de tenerte como parte de nuestra comunidad de salud digital. SaludOne te ayudará a gestionar tu información médica de manera segura y eficiente.</p>

            <div class="features">
                <h3 style="color: #1f2937; margin-bottom: 20px;">¿Qué puedes hacer en SaludOne?</h3>
                
                <div class="feature">
                    <div class="feature-icon">📋</div>
                    <div class="feature-text">
                        <div class="feature-title">Gestiona tus registros médicos</div>
                        <div class="feature-description">Mantén un historial completo y organizado de tu información médica</div>
                    </div>
                </div>

                <div class="feature">
                    <div class="feature-icon">📅</div>
                    <div class="feature-text">
                        <div class="feature-title">Programa citas médicas</div>
                        <div class="feature-description">Coordina tus citas con profesionales de la salud de manera fácil</div>
                    </div>
                </div>

                <div class="feature">
                    <div class="feature-icon">💬</div>
                    <div class="feature-text">
                        <div class="feature-title">Comunícate con tu equipo médico</div>
                        <div class="feature-description">Mantén contacto directo con doctores y personal médico</div>
                    </div>
                </div>

                <div class="feature">
                    <div class="feature-icon">🔒</div>
                    <div class="feature-text">
                        <div class="feature-title">Información segura y privada</div>
                        <div class="feature-description">Tus datos están protegidos con los más altos estándares de seguridad</div>
                    </div>
                </div>
            </div>

            <div class="cta-section">
                <h3 style="color: #1f2937; margin-bottom: 15px;">¡Comienza ahora!</h3>
                <p>Completa tu perfil para acceder a todas las funcionalidades de SaludOne.</p>
                <a href="#" class="button">Completar mi perfil</a>
            </div>

            <p><strong>Próximos pasos:</strong></p>
            <ul style="color: #6b7280;">
                <li>Completa tu información personal en el perfil</li>
                <li>Agrega tu información médica relevante</li>
                <li>Explora las funcionalidades disponibles</li>
                <li>¡Disfruta de una experiencia de salud digital completa!</li>
            </ul>
        </div>

        <div class="footer">
            <div class="social-links">
                <a href="#">Soporte</a> |
                <a href="#">Ayuda</a> |
                <a href="#">Términos de uso</a>
            </div>
            <p>Este email fue enviado a {{ $userEmail }}</p>
            <p>© {{ date('Y') }} SaludOne. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
