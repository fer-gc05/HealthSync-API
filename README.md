## HealthSync API – Portal Web de Coordinación de Citas y Teleasistencia

Aplicación web orientada a clínicas y centros de salud para gestionar citas presenciales y virtuales, historiales médicos y comunicación con pacientes. Proyecto vertical: Web App. Sector: HealthTech.

### Necesidad del cliente
- Interoperabilidad con sistemas EHR (FHIR)
- Gestión de citas y agendas (presencial/virtual)
- Historiales médicos electrónicos
- Comunicación con pacientes y recordatorios automáticos

Problema actual: muchos sistemas son fragmentados, duplican datos y generan errores de agenda.

### Validación de mercado
- Énfasis en interoperabilidad, seguridad de datos y UX en guías de salud digital
- ~60% de hospitales adoptan herramientas predictivas y gestión remota → demanda creciente de teleasistencia integrada

### Expectativa del producto
- Pacientes: agendar/modificar citas, recibir recordatorios, ver historial clínico
- Médicos: administrar agenda, revisar historiales, lanzar teleconsultas
- Integración con videollamadas (WebRTC/Zoom) y sistemas EHR existentes

---

## Estado actual del proyecto
- Autenticación básica de API implementada con JWT (login, registro, sesión, refresh)
- **✅ Sistema de roles y permisos implementado con Spatie Permission**
- **✅ Middlewares de autorización configurados**
- Documentación OpenAPI generada automáticamente con Scramble
- Rutas públicas de estado y enlaces a documentación en `routes/web.php`

Próximos pasos
- ~~Control de roles y permisos con Spatie (a integrar)~~ ✅ **COMPLETADO**
- Tiempo real y websockets con Reverb (a integrar)
- Módulos clínicos (EHR/FHIR), teleconsulta y recordatorios

---

## Endpoints API actuales
Archivo `routes/api.php`:

**Autenticación:**
- POST `/api/auth/register` – registro (requiere campo `role`: "patient" o "doctor")
- POST `/api/auth/login` – login
- GET `/api/auth/me` – perfil (protegido `auth:api` con JWT)
- POST `/api/auth/logout` – cerrar sesión (protegido)
- POST `/api/auth/refresh` – refrescar token (protegido)

**Administración (solo admin):**
- GET `/api/admin/users` – listar usuarios con roles
- GET `/api/admin/users/role/{role}` – usuarios por rol específico
- PUT `/api/admin/users/{user}/role` – asignar rol a usuario

Archivo `routes/web.php` expone metadata y enlaces útiles:
- `GET /` → JSON con nombre del proyecto, versión de Laravel y enlaces:
  - UI de documentación: `/docs/v1/api`
  - Especificación OpenAPI JSON: `/docs/v1/openapi.json`

---

## Sistema de Roles y Permisos

### Roles disponibles:
- **admin**: Gestión completa del sistema
- **doctor**: Acceso a pacientes y gestión de citas  
- **patient**: Acceso limitado a datos propios

### Permisos por rol:
- **Admin**: `manage-users`, `manage-doctors`, `manage-patients`, `view-reports`, `manage-system`
- **Doctor**: `view-patients`, `create-appointments`, `update-appointments`, `view-medical-records`
- **Patient**: `view-own-profile`, `create-appointments`, `view-own-appointments`, `view-own-medical-records`

### Asignación de roles:
1. **Registro público**: Campo `role` obligatorio ("patient" o "doctor")
2. **Gestión admin**: Endpoint `PUT /api/admin/users/{user}/role`

### Usuarios de prueba:
- Por el momento no hay usuarios predefinidos. Crear mediante registro o Tinker.

### Ejemplos:
```bash
# Registro como doctor
POST /api/auth/register
{
  "name": "Dr. García",
  "email": "garcia@hospital.com", 
  "password": "segura123",
  "password_confirmation": "segura123",
  "role": "doctor"
}

# Admin cambia rol de usuario
PUT /api/admin/users/5/role
Authorization: Bearer {admin_token}
{
  "role": "admin"
}
```

---

## Paquetes en uso
- Tymon JWT Auth: autenticación JWT para APIs
  - Documentación: [`https://jwt-auth.readthedocs.io/en/develop/laravel-installation/`](https://jwt-auth.readthedocs.io/en/develop/laravel-installation/)
- Dedoc Scramble: generación automática de documentación OpenAPI (Swagger) para Laravel
  - Documentación: [`https://scramble.dedoc.co/`](https://scramble.dedoc.co/)
- Spatie Permission: gestión de roles y permisos
  - Documentación: [`https://spatie.be/docs/laravel-permission/v5/introduction`](https://spatie.be/docs/laravel-permission/v5/introduction)

Nota: Más adelante se integrará Reverb para websockets/tiempo real.

---

## Instalación y puesta en marcha
Requisitos: PHP 8.2+, Composer, SQLite (por defecto) u otro driver soportado por Laravel.

1) Clonar e instalar dependencias
```bash
# HTTPS
git clone https://github.com/fer-gc05/HealthSync-API.git

# SSH
git clone git@github.com:fer-gc05/HealthSync-API.git

cd HealthSync-API
composer install
```

2) Variables de entorno y clave de app
```bash
cp .env.example .env
php artisan key:generate
```

3) Base de datos (SQLite por defecto en `database/database.sqlite`)
```bash
php artisan migrate
```

4) Seeders: crear roles y permisos
```bash
php artisan db:seed --class=RolePermissionSeeder
```

5) Crear usuario administrador (Opcional)
```bash
php artisan tinker
>>> $admin = App\Models\User::create(['name' => 'Admin', 'email' => 'admin@api.com', 'password' => Hash::make('HealthSync')]);
>>> $admin->assignRole('admin');
>>> exit
```

6) JWT Auth: generar secreto (la configuración ya está publicada)
```bash
php artisan jwt:secret
```

7) Scramble: documentación API
Por defecto, Scramble expone la UI y el JSON bajo `/docs/v1/api` y `/docs/v1/openapi.json`. Ajustes en `config/scramble.php`.

8) Ejecutar en desarrollo
```bash
php artisan serve
```

---

## Roadmap funcional
Must-have
- Registro de pacientes y autenticación segura (en progreso: JWT)
- Gestión de citas con disponibilidad en tiempo real
- Recordatorios automáticos (correo/SMS)
- Teleconsulta con video y chat seguro
- Integración EHR (FHIR) lectura/escritura

Nice-to-have
- Asignación de citas según prioridad médica
- Facturación por sesión
- Gestión de listas de espera y redistribución
- Analítica para predecir cancelaciones y no-shows

---

## Convención de commits (gitmojis)
Usamos emojis para expresar la intención del cambio. Formato sugerido:
`<emoji> <tipo breve>: <mensaje conciso>`

Ejemplos útiles

- ✨ `:sparkles:`: nueva funcionalidad
- 🐛 `:bug:`: corrección de bug
- 🔧 `:wrench:`: configuración (env, build, CI)
- 📝 `:memo:`: documentación/README
- 🎨 `:art:`: mejoras de estilo/formato (sin cambiar lógica)
- ♻️ `:recycle:`: refactor
- ✅ `:white_check_mark:`: tests añadidos/actualizados
- ⬆️ `:arrow_up:`: actualización de dependencias
- ⬇️ `:arrow_down:`: degradación de dependencias
- 🚑️ `:ambulance:`: hotfix crítico en producción
- 🔒 `:lock:`: mejoras de seguridad
- 🚧 `:construction:`: trabajo en progreso (WIP)
- 🧪 `:test_tube:`: experimentos/prototipos
- 📦 `:package:`: empaquetado/compilados

Ejemplos
- `✨ feat: endpoint /auth/register con validación`
- `🐛 fix: refresco de JWT corrige 401 intermitente`
- `📝 docs: enlaza UI de Scramble y OpenAPI JSON`

Si usas `gitmoji-cli`, puedes iniciar con: `gitmoji -c`

---

## Contribución
- Las tareas para los otros backends se asignarán mediante Issues en el repositorio.
- Todos los cambios deben enviarse como Pull Requests apuntando a la rama `develop`.
- Evita commits directos a `main`. Usa la convención de commits indicada arriba.

## Enlaces de referencia
- Scramble – Laravel OpenAPI (Swagger) Documentation Generator: [`https://scramble.dedoc.co/`](https://scramble.dedoc.co/)
- Tymon JWT Auth – Instalación en Laravel: [`https://jwt-auth.readthedocs.io/en/develop/laravel-installation/`](https://jwt-auth.readthedocs.io/en/develop/laravel-installation/)
- Spatie Permission – Documentación en Laravel: [`https://spatie.be/docs/laravel-permission/v5/introduction`](https://spatie.be/docs/laravel-permission/v5/introduction)

---
