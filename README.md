## SaludOne API – Portal Web de Coordinación de Citas y Teleasistencia

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
- **✅ Autenticación completa de API implementada con JWT**
- **✅ Sistema de roles y permisos implementado con Spatie Permission**
- **✅ CRUD completo de usuarios para administradores**
- **✅ Soft Deletes implementados en todos los modelos principales**
- **✅ Middlewares de autorización configurados**
- **✅ Gestión de perfiles de paciente y doctor**
- **✅ Filtros y búsqueda avanzada de usuarios**
- **✅ Google OAuth integrado con Laravel Socialite**
- **✅ Autenticación OAuth con Google (stateless)**
- **✅ Campos OAuth en tabla users (google_id, google_token, etc.)**
- **✅ Password nullable para usuarios OAuth**
- **✅ CRUD completo y avanzado de registros médicos**
- **✅ Sistema de auditoría completo para cambios en registros médicos**
- **✅ Gestión de archivos adjuntos con validaciones de seguridad**
- **✅ Validaciones complejas de datos médicos (signos vitales, prescripciones)**
- **✅ Sistema de versionado de registros médicos**
- **✅ Permisos granulares por rol para registros médicos**
- **✅ Integración completa con sistema de roles y citas existente**
- **✅ Sistema completo de gestión de citas médicas**
- **✅ Algoritmo inteligente de asignación automática de doctores**
- **✅ Sistema de lista de espera para citas**
- **✅ Integración completa con Google Calendar**
- **✅ Teleconsulta con Google Meet automática**
- **✅ Gestión de disponibilidad de doctores**
- **✅ Recordatorios automáticos por email**
- **✅ Sincronización bidireccional con Google Calendar**
- **✅ Controlador independiente de Google Calendar**
- **✅ Comandos Artisan para gestión de tokens de Google**
- Documentación OpenAPI generada automáticamente con Scramble
- Rutas públicas de estado y enlaces a documentación en `routes/web.php`

Próximos pasos
- ~~Control de roles y permisos con Spatie~~ ✅ **COMPLETADO**
- ~~CRUD de usuarios y Soft Deletes~~ ✅ **COMPLETADO**
- ~~Google OAuth con Laravel Socialite~~ ✅ **COMPLETADO**
- ~~CRUD avanzado de registros médicos~~ ✅ **COMPLETADO**
- ~~Sistema de auditoría de registros médicos~~ ✅ **COMPLETADO**
- ~~Gestión de archivos adjuntos~~ ✅ **COMPLETADO**
- ~~Sistema completo de gestión de citas~~ ✅ **COMPLETADO**
- ~~Integración con Google Calendar~~ ✅ **COMPLETADO**
- ~~Teleconsulta con Google Meet~~ ✅ **COMPLETADO**
- Tiempo real y websockets con Reverb (a integrar)
- Módulos clínicos avanzados (EHR/FHIR)

---

## 🔐 API Endpoints Disponibles

### **Autenticación**
```
POST /api/auth/register          - Registro de usuario
POST /api/auth/login             - Inicio de sesión
GET  /api/auth/me                - Perfil del usuario autenticado
POST /api/auth/refresh           - Renovar token
POST /api/auth/logout            - Cerrar sesión

# Google OAuth
GET  /api/auth/google/redirect   - Redirigir a Google OAuth
GET  /api/auth/google/callback   - Callback de Google OAuth
POST /api/auth/google/link       - Vincular cuenta Google (autenticado)
DELETE /api/auth/google/unlink   - Desvincular cuenta Google (autenticado)
GET  /api/auth/google/status     - Estado de vinculación Google (autenticado)
```

### **Gestión de Perfil (Usuarios Autenticados)**
```
PUT  /api/profile                - Actualizar perfil básico
POST /api/profile/complete/patient - Completar perfil de paciente
PUT  /api/profile/patient        - Actualizar datos de paciente
```

### **Administración (Solo Admin)**
```
# Gestión de usuarios y roles
GET    /api/admin/users                    - Listar usuarios (con filtros y paginación)
GET    /api/admin/users/role/{role}       - Usuarios por rol específico
PUT    /api/admin/users/{user}/role        - Asignar rol a usuario

# CRUD de usuarios
GET    /api/admin/users/trashed           - Usuarios eliminados
GET    /api/admin/users/{user}            - Ver usuario específico
DELETE /api/admin/users/{user}            - Eliminar usuario (soft delete)
POST   /api/admin/users/{user}/restore    - Restaurar usuario
DELETE /api/admin/users/{user}/force      - Eliminar permanentemente

# Crear usuarios por rol
POST   /api/admin/users/admin             - Crear administrador
POST   /api/admin/users/patient          - Crear paciente
POST   /api/admin/users/doctor           - Crear doctor

# Actualizar usuarios por rol
PUT    /api/admin/users/{user}/admin     - Actualizar administrador
PUT    /api/admin/users/{user}/patient   - Actualizar paciente
PUT    /api/admin/users/{user}/doctor    - Actualizar doctor
```

### **Sistema de Citas Médicas**

#### **Para Administradores**
```
# Gestión completa de citas
GET    /api/admin/appointments                    - Listar todas las citas (con filtros)
POST   /api/admin/appointments                    - Crear cita administrativa
GET    /api/admin/appointments/{id}               - Ver cita específica
PUT    /api/admin/appointments/{id}               - Actualizar cita
DELETE /api/admin/appointments/{id}               - Eliminar cita

# Estadísticas y gestión
GET    /api/admin/appointments/stats              - Estadísticas de citas
GET    /api/admin/appointments/availability       - Disponibilidad de doctores
POST   /api/admin/appointments/assign-optimal     - Asignar doctor óptimo
POST   /api/admin/appointments/{id}/sync-google   - Sincronizar con Google Calendar
```

#### **Para Doctores**
```
# Gestión de citas del doctor
GET    /api/doctor/appointments                   - Mis citas (con filtros)
GET    /api/doctor/appointments/{id}              - Ver cita específica
PUT    /api/doctor/appointments/{id}              - Actualizar cita
POST   /api/doctor/appointments/schedule         - Programar nueva cita

# Disponibilidad y agenda
GET    /api/doctor/appointments/today             - Citas de hoy
GET    /api/doctor/appointments/this-week         - Citas de esta semana
GET    /api/doctor/appointments/availability      - Mi disponibilidad
PUT    /api/doctor/appointments/availability      - Actualizar disponibilidad
GET    /api/doctor/appointments/waitlist          - Lista de espera

# Teleconsulta
POST   /api/doctor/appointments/{id}/start-teleconsultation - Iniciar teleconsulta
POST   /api/doctor/appointments/{id}/end-teleconsultation  - Finalizar teleconsulta
```

#### **Para Pacientes**
```
# Gestión de citas del paciente
GET    /api/patient/appointments                  - Mis citas (con filtros)
GET    /api/patient/appointments/upcoming         - Próximas citas
GET    /api/patient/appointments/history          - Historial de citas
POST   /api/patient/appointments/book             - Reservar cita
PUT    /api/patient/appointments/{id}/reschedule - Reprogramar cita
POST   /api/patient/appointments/{id}/cancel     - Cancelar cita

# Consultas y teleconsulta
GET    /api/patient/appointments/available-slots  - Horarios disponibles
GET    /api/patient/appointments/available-doctors - Doctores disponibles
GET    /api/patient/appointments/{id}/teleconsultation-link - Enlace de teleconsulta
```

### **Google Calendar (Independiente)**
```
# Autenticación OAuth
GET    /api/calendar/auth/google                  - Redirigir a Google OAuth
GET    /api/calendar/auth/google/callback         - Callback de Google OAuth

# Gestión de eventos
GET    /api/calendar/events                       - Listar eventos
POST   /api/calendar/events                       - Crear evento
GET    /api/calendar/events/{id}                  - Ver evento específico
PUT    /api/calendar/events/{id}                   - Actualizar evento
DELETE /api/calendar/events/{id}                  - Eliminar evento
```

### **Registros Médicos**

#### **Para Doctores**
```
# CRUD de registros médicos
GET    /api/medical-records                    - Listar registros del doctor
POST   /api/medical-records                    - Crear registro médico
GET    /api/medical-records/{id}               - Ver registro específico
PUT    /api/medical-records/{id}               - Actualizar registro médico
DELETE /api/medical-records/{id}               - Eliminar registro médico

# Funciones específicas
GET    /api/medical-records/patient/{patient_id} - Registros de un paciente específico
GET    /api/medical-records/{id}/history         - Historial de cambios del registro
GET    /api/medical-records/{id}/audit           - Log completo de auditoría
```

#### **Para Pacientes**
```
GET    /api/medical-records                    - Mis registros médicos (solo lectura)
GET    /api/medical-records/{id}               - Ver mi registro específico
```

#### **Para Administradores**
```
# CRUD administrativo completo
GET    /api/admin/medical-records              - Listar todos los registros médicos
POST   /api/admin/medical-records              - Crear registro médico
GET    /api/admin/medical-records/{id}         - Ver cualquier registro médico
PUT    /api/admin/medical-records/{id}         - Actualizar cualquier registro médico
DELETE /api/admin/medical-records/{id}         - Eliminar cualquier registro médico
GET    /api/admin/medical-records/{id}/audit   - Auditoría de cualquier registro
```

#### **Gestión de Archivos Adjuntos**
```
# Para doctores y administradores
POST   /api/medical-records/{id}/files         - Subir archivo adjunto
GET    /api/medical-records/{id}/files         - Listar archivos del registro
GET    /api/medical-records/{id}/files/{file_id} - Descargar archivo
DELETE /api/medical-records/{id}/files/{file_id} - Eliminar archivo
```

> **Nota:** La ruta `GET /api/admin/users` está duplicada en el código (líneas 38 y 43) pero ambas apuntan a controladores diferentes. La primera usa `UserRoleController::users` y la segunda usa `UsersController::index`. Laravel usará la primera definición.

## 🔍 Parámetros de Búsqueda

### **Listar Usuarios**
```
GET /api/admin/users?q=nombre&role=patient&with_trashed=true&per_page=15&page=1
```

**Parámetros:**
- `q` (string): Búsqueda por nombre o email
- `role` (string): Filtro por rol (admin, doctor, patient)
- `with_trashed` (boolean): Incluir usuarios eliminados
- `only_trashed` (boolean): Solo usuarios eliminados
- `per_page` (integer): Elementos por página (1-100)
- `page` (integer): Número de página
- `sort_by` (string): Campo de ordenamiento
- `sort_dir` (string): Dirección (asc/desc)

### **Listar Registros Médicos**

#### **Para Doctores**
```
GET /api/medical-records?patient_id=1&date_from=2024-01-01&date_to=2024-12-31&has_prescriptions=true&q=dolor&per_page=15
```

#### **Para Pacientes**
```
GET /api/medical-records?date_from=2024-01-01&date_to=2024-12-31&per_page=15
```

#### **Para Administradores**
```
GET /api/admin/medical-records?patient_id=1&medical_staff_id=2&date_from=2024-01-01&has_files=true&q=diagnóstico&per_page=15
```

**Parámetros disponibles:**
- `patient_id` (integer): Filtrar por paciente específico
- `medical_staff_id` (integer): Filtrar por doctor específico (solo admin)
- `appointment_id` (integer): Filtrar por cita específica
- `date_from` (date): Fecha de inicio (YYYY-MM-DD)
- `date_to` (date): Fecha de fin (YYYY-MM-DD)
- `has_prescriptions` (boolean): Solo registros con prescripciones
- `has_files` (boolean): Solo registros con archivos adjuntos
- `q` (string): Búsqueda en contenido médico (subjetivo, objetivo, evaluación, plan, prescripciones)
- `per_page` (integer): Elementos por página (1-50, default: 15)
- `page` (integer): Número de página

## 🔐 Autenticación

Todos los endpoints requieren autenticación JWT:

```bash
curl -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     http://127.0.0.1:8000/api/endpoint
```

Archivo `routes/web.php` expone metadata y enlaces útiles:
- `GET /` → JSON con nombre del proyecto, versión de Laravel y enlaces:
  - UI de documentación: `/docs/v1/api`
  - Especificación OpenAPI JSON: `/docs/v1/openapi.json`

---

## 👥 Roles y Permisos

### **Roles disponibles:**
- **admin**: Gestión completa del sistema
- **doctor**: Acceso a pacientes y gestión de citas  
- **patient**: Acceso limitado a datos propios

### **Permisos por rol:**
- **Admin**: `manage-users`, `manage-doctors`, `manage-patients`, `view-reports`, `manage-system`, `manage-medical-records`, `view-all-medical-records`
- **Doctor**: `view-patients`, `create-appointments`, `update-appointments`, `view-medical-records`, `create-medical-records`, `update-medical-records`, `delete-medical-records`, `manage-medical-files`
- **Patient**: `view-own-profile`, `create-appointments`, `view-own-appointments`, `view-own-medical-records`

### **Asignación de roles:**
1. **Registro público**: Campo `role` obligatorio ("patient" o "doctor")
2. **Gestión admin**: Endpoint `PUT /api/admin/users/{user}/role`

## 🗑️ Soft Deletes

El sistema implementa soft deletes para mantener la integridad de datos:

- Los usuarios eliminados se marcan como `deleted_at`
- Las relaciones se eliminan en cascada
- Los usuarios pueden ser restaurados
- Los administradores pueden ver usuarios eliminados
- Eliminación permanente disponible para casos especiales

## 📋 Sistema de Registros Médicos

### **Características Principales**

#### **CRUD Avanzado**
- **Doctores**: CRUD completo de sus propios registros médicos
- **Pacientes**: Acceso de solo lectura a sus registros médicos
- **Administradores**: CRUD completo de todos los registros médicos

#### **Sistema de Auditoría Completo**
- **Log automático** de todos los cambios (crear, actualizar, eliminar)
- **Valores anteriores y nuevos** registrados en cada modificación
- **Información del usuario** que realizó cada acción
- **IP address y User Agent** capturados para trazabilidad
- **Timestamps precisos** de cada modificación
- **Acceso a auditoría** incluso de registros eliminados

#### **Gestión de Archivos Adjuntos**
- **Subida segura** de archivos (PDF, DOC, DOCX, JPG, PNG, TXT)
- **Validación de tipos** y tamaños de archivo (máximo 10MB)
- **Almacenamiento privado** en disco seguro
- **Descarga segura** con verificación de permisos
- **Metadatos completos** (nombre original, tamaño, tipo MIME, descripción)

#### **Validaciones Médicas Complejas**
- **Signos vitales** con rangos válidos (presión arterial, frecuencia cardíaca, temperatura)
- **Formato de prescripciones** médicas
- **Validación de diagnósticos** y evaluaciones
- **Integridad de datos** médicos

#### **Filtros y Búsqueda Avanzada**
- **Filtros por paciente, doctor, cita**
- **Filtros por fechas** (rango de fechas)
- **Filtros por contenido** (prescripciones, archivos adjuntos)
- **Búsqueda semántica** en contenido médico
- **Paginación** optimizada

### **Estructura de Datos**

#### **Campos del Registro Médico**
```json
{
  "appointment_id": 1,
  "patient_id": 1,
  "medical_staff_id": 1,
  "subjective": "Síntomas reportados por el paciente",
  "objective": "Hallazgos del examen físico",
  "assessment": "Evaluación y diagnóstico",
  "plan": "Plan de tratamiento",
  "vital_signs": {
    "blood_pressure": "120/80",
    "heart_rate": 75,
    "temperature": 36.5,
    "respiratory_rate": 18,
    "oxygen_saturation": 98,
    "weight": 70,
    "height": 175
  },
  "prescriptions": "Medicamentos prescritos",
  "recommendations": "Recomendaciones para el paciente"
}
```

#### **Sistema de Auditoría**
```json
{
  "medical_record_id": 1,
  "user_id": 1,
  "action": "created|updated|deleted|restored|force_deleted",
  "old_values": {...},
  "new_values": {...},
  "ip_address": "127.0.0.1",
  "user_agent": "curl/8.11.1",
  "created_at": "2024-01-01T00:00:00Z"
}
```

### **Permisos Granulares**

#### **Doctores**
- ✅ Crear registros médicos para sus pacientes
- ✅ Ver solo sus propios registros médicos
- ✅ Actualizar solo sus propios registros médicos
- ✅ Eliminar solo sus propios registros médicos
- ✅ Subir archivos a sus registros médicos
- ✅ Ver auditoría de sus registros médicos

#### **Pacientes**
- ✅ Ver solo sus propios registros médicos
- ✅ Descargar archivos de sus registros médicos
- ❌ No pueden crear, editar o eliminar registros
- ❌ No pueden subir archivos

#### **Administradores**
- ✅ Acceso completo a todos los registros médicos
- ✅ Crear registros médicos para cualquier doctor/paciente
- ✅ Ver, editar y eliminar cualquier registro médico
- ✅ Ver auditoría de cualquier registro médico
- ✅ Gestionar archivos de cualquier registro médico

## 📊 Respuestas de la API

### **Éxito**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### **Error**
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }
}
```

### **Paginación**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "first_page_url": "...",
    "from": 1,
    "last_page": 5,
    "last_page_url": "...",
    "next_page_url": "...",
    "path": "...",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 75
  }
}
```

### **Ejemplos de Respuestas Reales**

**Login exitoso (Admin):**
```json
{
  "success": true,
  "message": "Login successful",
  "role": "admin",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3NjA4NDUzNTUsImV4cCI6MTc2MDg0ODk1NSwibmJmIjoxNzYwODQ1MzU1LCJqdGkiOiJyekdnTFdPWTI0Vkp2QVNWIiwic3ViIjoiMjgiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3Iiwicm9sZSI6ImFkbWluIiwicGVybWlzc2lvbnMiOlsibWFuYWdlLXVzZXJzIiwibWFuYWdlLWRvY3RvcnMiLCJtYW5hZ2UtcGF0aWVudHMiLCJtYW5hZ2Utc3BlY2lhbHRpZXMiLCJtYW5hZ2UtYXBwb2ludG1lbnRzIiwibWFuYWdlLW1lZGljYWwtcmVjb3JkcyIsInZpZXctcmVwb3J0cyIsInZpZXctYW5hbHl0aWNzIiwibWFuYWdlLXN5c3RlbSIsIm1hbmFnZS1ub3RpZmljYXRpb25zIiwidmlldy1hbGwtYXBwb2ludG1lbnRzIiwiY2FuY2VsLWFueS1hcHBvaW50bWVudCIsImFzc2lnbi1hcHBvaW50bWVudHMiXX0.5h27kBInZYZ10Xct-oJR2r6nM0BkKdG6hdIHknft1zs"
}
```

**Login exitoso (Doctor):**
```json
{
  "success": true,
  "message": "Login successful",
  "role": "doctor",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3NjA4NDUzNTksImV4cCI6MTc2MDg0ODk1OSwibmJmIjoxNzYwODQ1MzU5LCJqdGkiOiJ6UUhSMTVSU0FkQXh6U2VmIiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjciLCJyb2xlIjoiZG9jdG9yIiwicGVybWlzc2lvbnMiOlsidmlldy1wYXRpZW50cyIsInZpZXctcGF0aWVudC1kZXRhaWxzIiwiY3JlYXRlLWFwcG9pbnRtZW50cyIsInVwZGF0ZS1hcHBvaW50bWVudHMiLCJjYW5jZWwtYXBwb2ludG1lbnRzIiwidmlldy1tZWRpY2FsLXJlY29yZHMiLCJjcmVhdGUtbWVkaWNhbC1yZWNvcmRzIiwidXBkYXRlLW1lZGljYWwtcmVjb3JkcyIsInZpZXctb3duLWFwcG9pbnRtZW50cyIsIm1hbmFnZS1hdmFpbGFiaWxpdHkiLCJ2aWV3LXBhdGllbnQtaGlzdG9yeSIsInByZXNjcmliZS1tZWRpY2F0aW9ucyIsInJlcXVlc3QtdGVzdHMiXX0.M27n_ay_ssGAt5I6j0uPd-hs3ZnXmmzZDJYpTfL5ghc"
}
```

**Login exitoso (Paciente):**
```json
{
  "success": true,
  "message": "Login successful",
  "role": "patient",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3NjA4NDUzNjMsImV4cCI6MTc2MDg0ODk2MywibmJmIjoxNzYwODQ1MzYzLCJqdGkiOiI2MU9oUlQ1bFAxMkx1Nk1LIiwic3ViIjoiMTMiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3Iiwicm9sZSI6InBhdGllbnQiLCJwZXJtaXNzaW9ucyI6WyJjcmVhdGUtYXBwb2ludG1lbnRzIiwidmlldy1vd24tYXBwb2ludG1lbnRzIiwidmlldy1vd24tcHJvZmlsZSIsInVwZGF0ZS1vd24tcHJvZmlsZSIsImNhbmNlbC1vd24tYXBwb2ludG1lbnRzIiwidmlldy1vd24tbWVkaWNhbC1yZWNvcmRzIiwidmlldy1vd24tcHJlc2NyaXB0aW9ucyIsInZpZXctb3duLXRlc3QtcmVzdWx0cyIsInNlbmQtbWVzc2FnZXMiLCJ2aWV3LW5vdGlmaWNhdGlvbnMiXX0.s64RXdV13FyplLzeS5T1b1opv__iAvn0r9Aft9eKRcY"
}
```

**Registro exitoso:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "role": "patient",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**Error de validación:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

**Error de autorización:**
```json
{
  "message": "User does not have the right roles.",
  "exception": "Spatie\\Permission\\Exceptions\\UnauthorizedException"
}
```

**Error de credenciales inválidas:**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Error de token expirado:**
```json
{
  "success": false,
  "message": "Token has expired"
}
```

**Error de usuario no encontrado:**
```json
{
  "success": false,
  "message": "User not found"
}
```

**Usuario creado exitosamente:**
```json
{
  "success": true,
  "message": "Patient created successfully",
  "data": {
    "id": 17,
    "name": "New Patient",
    "email": "new.patient@saludone.com",
    "patient": {
      "id": 7,
      "birth_date": "1985-05-15",
      "gender": "female",
      "phone": "+1234567890"
    },
    "roles": [{"name": "patient"}]
  }
}
```

## 🧪 Ejemplos de Uso

### **Registro de Usuario**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### **Crear Paciente (Admin)**
```bash
curl -X POST http://127.0.0.1:8000/api/admin/users/patient \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{
    "name": "María García",
    "email": "maria@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "birth_date": "1990-01-01",
    "gender": "female",
    "phone": "+1234567890",
    "address": "123 Main St",
    "blood_type": "A+",
    "allergies": "None",
    "current_medications": "None",
    "insurance_number": "INS123456",
    "emergency_contact_name": "Emergency Contact",
    "emergency_contact_phone": "+0987654321"
  }'
```

### **Buscar Usuarios**
```bash
curl -X GET "http://127.0.0.1:8000/api/admin/users?q=maria&role=patient&per_page=10" \
  -H "Authorization: Bearer {admin_token}"
```

### **Asignar Rol**
```bash
curl -X PUT http://127.0.0.1:8000/api/admin/users/15/role \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{"role": "doctor"}'
```

### **Ver Usuarios por Rol**
```bash
curl -X GET http://127.0.0.1:8000/api/admin/users/role/patient \
  -H "Authorization: Bearer {admin_token}"
```

### **Ver Usuarios Eliminados**
```bash
curl -X GET http://127.0.0.1:8000/api/admin/users/trashed \
  -H "Authorization: Bearer {admin_token}"
```

### **Restaurar Usuario**
```bash
curl -X POST http://127.0.0.1:8000/api/admin/users/15/restore \
  -H "Authorization: Bearer {admin_token}"
```

### **Registros Médicos**

#### **Crear Registro Médico (Doctor)**
```bash
curl -X POST http://127.0.0.1:8000/api/medical-records \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {doctor_token}" \
  -d '{
    "appointment_id": 1,
    "patient_id": 1,
    "subjective": "Paciente refiere dolor de cabeza desde hace 2 días",
    "objective": "Paciente alerta, orientado. Presión arterial: 130/85 mmHg",
    "assessment": "Cefalea tensional",
    "plan": "Reposo, analgésicos, seguimiento",
    "vital_signs": {
      "blood_pressure": "130/85",
      "heart_rate": 80,
      "temperature": 36.8
    },
    "prescriptions": "Ibuprofeno 400mg cada 8 horas",
    "recommendations": "Evitar estrés, descansar"
  }'
```

#### **Listar Registros Médicos con Filtros**
```bash
# Para doctores
curl -X GET "http://127.0.0.1:8000/api/medical-records?patient_id=1&date_from=2024-01-01&has_prescriptions=true" \
  -H "Authorization: Bearer {doctor_token}"

# Para pacientes
curl -X GET "http://127.0.0.1:8000/api/medical-records?date_from=2024-01-01" \
  -H "Authorization: Bearer {patient_token}"

# Para administradores
curl -X GET "http://127.0.0.1:8000/api/admin/medical-records?medical_staff_id=1&has_files=true" \
  -H "Authorization: Bearer {admin_token}"
```

#### **Subir Archivo Adjunto**
```bash
curl -X POST http://127.0.0.1:8000/api/medical-records/1/files \
  -H "Authorization: Bearer {doctor_token}" \
  -F "file=@documento.pdf" \
  -F "description=Radiografía de tórax"
```

#### **Ver Auditoría de Registro Médico**
```bash
curl -X GET http://127.0.0.1:8000/api/medical-records/1/audit \
  -H "Authorization: Bearer {doctor_token}"
```

#### **Descargar Archivo**
```bash
curl -X GET http://127.0.0.1:8000/api/medical-records/1/files/1 \
  -H "Authorization: Bearer {doctor_token}" \
  -o archivo_descargado.pdf
```

---

## Paquetes en uso
- **Tymon JWT Auth**: autenticación JWT para APIs
  - Documentación: [`https://jwt-auth.readthedocs.io/en/develop/laravel-installation/`](https://jwt-auth.readthedocs.io/en/develop/laravel-installation/)
- **Dedoc Scramble**: generación automática de documentación OpenAPI (Swagger) para Laravel
  - Documentación: [`https://scramble.dedoc.co/`](https://scramble.dedoc.co/)
- **Spatie Permission**: gestión de roles y permisos
  - Documentación: [`https://spatie.be/docs/laravel-permission/v5/introduction`](https://spatie.be/docs/laravel-permission/v5/introduction)
- **Laravel Socialite**: autenticación OAuth con Google
  - Documentación: [`https://laravel.com/docs/socialite`](https://laravel.com/docs/socialite)
- **Google API Client**: integración con Google Calendar API
  - Documentación: [`https://developers.google.com/calendar/api`](https://developers.google.com/calendar/api)
- **Laravel Mail**: sistema de notificaciones por email
  - Documentación: [`https://laravel.com/docs/mail`](https://laravel.com/docs/mail)
- **Laravel Queue**: procesamiento asíncrono de tareas
  - Documentación: [`https://laravel.com/docs/queues`](https://laravel.com/docs/queues)

### **Nuevas Funcionalidades Implementadas**

#### **Sistema de Citas Médicas**
- **Algoritmo de asignación automática**: Scoring inteligente basado en carga de trabajo, experiencia y disponibilidad
- **Lista de espera**: Gestión automática cuando no hay doctores disponibles
- **Sincronización con Google Calendar**: Creación, actualización y eliminación automática de eventos
- **Teleconsulta con Google Meet**: Enlaces generados automáticamente para citas virtuales
- **Gestión de disponibilidad**: Horarios flexibles por doctor con restricciones específicas
- **Recordatorios automáticos**: Emails personalizados para doctores y pacientes

#### **Integración Google Calendar**
- **OAuth 2.0**: Autenticación segura con Google
- **Renovación automática de tokens**: Gestión transparente de credenciales
- **Controlador independiente**: API para gestión directa de eventos
- **Comandos Artisan**: `google:check-token` y `google:refresh-token`

#### **Servicios Especializados**
- **AppointmentAssignmentService**: Lógica de asignación y disponibilidad
- **AppointmentCalendarService**: Sincronización con Google Calendar
- **GoogleCalendarService**: Interfaz con Google Calendar API
- **Jobs asíncronos**: Procesamiento en background de tareas pesadas

Nota: Más adelante se integrará Reverb para websockets/tiempo real.

---

## Instalación y puesta en marcha
Requisitos: PHP 8.2+, Composer, SQLite (por defecto) u otro driver soportado por Laravel.

1) Clonar e instalar dependencias
```bash
# HTTPS
git clone https://github.com/fer-gc05/SaludOne-API.git

# SSH
git clone git@github.com:fer-gc05/SaludOne-API.git

cd SaludOne-API
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

4) Seeders: crear roles, permisos y datos de prueba
```bash
php artisan migrate:fresh --seed
```

Esto ejecutará automáticamente:
- `RolePermissionSeeder` - Crea roles y permisos
- `SpecialtySeeder` - Crea especialidades médicas
- `MedicalStaffSeeder` - Crea personal médico de prueba
- `PatientSeeder` - Crea pacientes de prueba
- `AdminUserSeeder` - Crea usuarios administradores

**Usuarios administradores creados:**
- Fernando Gil (fernando.gil@saludone.com)
- Franco Maidana (franco.maidana@saludone.com)
- Sebastian Lemus (sebastian.lemus@saludone.com)

**Contraseña por defecto:** `admin123`

**Personal médico creado:**
- Dr. Juan Pérez (juan.perez@saludone.com)
- Dra. María García (maria.garcia@saludone.com)
- Dr. Carlos López (carlos.lopez@saludone.com)
- Dra. Ana Martínez (ana.martinez@saludone.com)
- Dr. Roberto Silva (roberto.silva@saludone.com)

**Pacientes de prueba creados:**
- Paciente Test 1 (paciente1@test.com)
- Paciente Test 2 (paciente2@test.com)
- Paciente Test 3 (paciente3@test.com)
- Paciente Test 4 (paciente4@test.com)
- Paciente Test 5 (paciente5@test.com)

**Contraseña para personal médico y pacientes:** `password123`

5) JWT Auth: generar secreto (la configuración ya está publicada)
```bash
php artisan jwt:secret
```

6) Scramble: documentación API
Por defecto, Scramble expone la UI y el JSON bajo `/docs/v1/api` y `/docs/v1/openapi.json`. Ajustes en `config/scramble.php`.

7) Ejecutar en desarrollo
```bash
php artisan serve
```

La API estará disponible en: `http://127.0.0.1:8000`

## 🧪 Testing de la API

### **Probar Autenticación**
```bash
# Login como admin
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "fernando.gil@saludone.com",
    "password": "admin123"
  }'

# Login como doctor
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan.perez@saludone.com",
    "password": "password123"
  }'

# Login como paciente
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "paciente1@test.com",
    "password": "password123"
  }'

# Respuesta esperada:
# {
#   "success": true,
#   "message": "Login successful",
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
# }
```

### **Probar Google OAuth**
```bash
# Redirigir a Google OAuth (abrir en navegador)
# http://127.0.0.1:8000/api/auth/google/redirect

# Vincular cuenta Google (requiere token de autenticación)
curl -X POST http://127.0.0.1:8000/api/auth/google/link \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "code": "authorization_code_from_google"
  }'

# Ver estado de vinculación Google
curl -X GET http://127.0.0.1:8000/api/auth/google/status \
  -H "Authorization: Bearer {token}"

# Desvincular cuenta Google
curl -X DELETE http://127.0.0.1:8000/api/auth/google/unlink \
  -H "Authorization: Bearer {token}"

# Respuesta esperada del callback:
# {
#   "success": true,
#   "message": "Google OAuth authentication successful",
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
#   "user": {
#     "id": 1,
#     "name": "Tu Nombre",
#     "email": "tu@email.com",
#     "has_google_account": true,
#     "role": "patient"
#   }
# }
```

### **Probar CRUD de Usuarios**
```bash
# Listar usuarios (requiere token de admin)
curl -X GET http://127.0.0.1:8000/api/admin/users \
  -H "Authorization: Bearer {admin_token}"

# Ver usuarios por rol
curl -X GET http://127.0.0.1:8000/api/admin/users/role/patient \
  -H "Authorization: Bearer {admin_token}"

# Crear paciente
curl -X POST http://127.0.0.1:8000/api/admin/users/patient \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{
    "name": "Test Patient",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "birth_date": "1990-01-01",
    "gender": "male",
    "phone": "+1234567890",
    "address": "123 Test St",
    "blood_type": "O+"
  }'

# Crear doctor
curl -X POST http://127.0.0.1:8000/api/admin/users/doctor \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{
    "name": "Test Doctor",
    "email": "doctor@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "professional_license": "LIC-123456",
    "specialty_id": 1,
    "active": true,
    "appointment_duration": 30
  }'

# Ver usuarios eliminados
curl -X GET http://127.0.0.1:8000/api/admin/users/trashed \
  -H "Authorization: Bearer {admin_token}"

# Respuesta esperada:
# {
#   "success": true,
#   "data": {
#     "current_page": 1,
#     "data": [...],
#     "total": 1
#   }
# }
```

### **Probar Sistema de Registros Médicos**

#### **Crear Registro Médico**
```bash
# Login como doctor
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan.perez@saludone.com",
    "password": "password123"
  }'

# Crear registro médico
curl -X POST http://127.0.0.1:8000/api/medical-records \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {doctor_token}" \
  -d '{
    "appointment_id": 1,
    "patient_id": 1,
    "subjective": "Paciente refiere dolor abdominal intenso",
    "objective": "Paciente con dolor a la palpación en cuadrante inferior derecho",
    "assessment": "Apendicitis aguda probable",
    "plan": "Solicitar laboratorios, ecografía abdominal",
    "vital_signs": {
      "blood_pressure": "120/80",
      "heart_rate": 95,
      "temperature": 38.1
    },
    "prescriptions": "Dipirona 500mg cada 6 horas",
    "recommendations": "Reposo absoluto, dieta líquida"
  }'

# Respuesta esperada:
# {
#   "success": true,
#   "data": {
#     "id": 1,
#     "appointment_id": 1,
#     "patient_id": 1,
#     "medical_staff_id": 1,
#     "subjective": "Paciente refiere dolor abdominal intenso",
#     "objective": "Paciente con dolor a la palpación...",
#     "assessment": "Apendicitis aguda probable",
#     "plan": "Solicitar laboratorios, ecografía abdominal",
#     "vital_signs": {...},
#     "prescriptions": "Dipirona 500mg cada 6 horas",
#     "recommendations": "Reposo absoluto, dieta líquida",
#     "patient": {...},
#     "medical_staff": {...},
#     "appointment": {...}
#   }
# }
```

#### **Probar Sistema de Auditoría**
```bash
# Ver auditoría del registro médico
curl -X GET http://127.0.0.1:8000/api/medical-records/1/audit \
  -H "Authorization: Bearer {doctor_token}"

# Respuesta esperada:
# {
#   "success": true,
#   "data": {
#     "current_page": 1,
#     "data": [
#       {
#         "id": 1,
#         "medical_record_id": 1,
#         "user_id": 1,
#         "action": "created",
#         "old_values": null,
#         "new_values": {...},
#         "ip_address": "127.0.0.1",
#         "user_agent": "curl/8.11.1",
#         "created_at": "2024-01-01T00:00:00Z",
#         "user": {...}
#       }
#     ],
#     "total": 1
#   }
# }
```

#### **Probar Gestión de Archivos**
```bash
# Crear archivo de prueba
echo "Este es un archivo de prueba" > test_file.txt

# Subir archivo
curl -X POST http://127.0.0.1:8000/api/medical-records/1/files \
  -H "Authorization: Bearer {doctor_token}" \
  -F "file=@test_file.txt" \
  -F "description=Archivo de prueba"

# Listar archivos
curl -X GET http://127.0.0.1:8000/api/medical-records/1/files \
  -H "Authorization: Bearer {doctor_token}"

# Descargar archivo
curl -X GET http://127.0.0.1:8000/api/medical-records/1/files/1 \
  -H "Authorization: Bearer {doctor_token}" \
  -o downloaded_file.txt

# Eliminar archivo
curl -X DELETE http://127.0.0.1:8000/api/medical-records/1/files/1 \
  -H "Authorization: Bearer {doctor_token}"
```

#### **Probar Permisos por Rol**
```bash
# Login como paciente
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "paciente1@test.com",
    "password": "password123"
  }'

# Intentar crear registro médico (debe fallar)
curl -X POST http://127.0.0.1:8000/api/medical-records \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {patient_token}" \
  -d '{"appointment_id": 1, "patient_id": 1}'

# Ver sus propios registros (debe funcionar)
curl -X GET http://127.0.0.1:8000/api/medical-records \
  -H "Authorization: Bearer {patient_token}"
```

### **Probar Sistema de Citas Médicas**

#### **Crear Cita como Paciente**
```bash
# Login como paciente
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "maria.gonzalez@email.com",
    "password": "password123"
  }'

# Crear cita virtual
curl -X POST http://127.0.0.1:8000/api/patient/appointments/book \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {patient_token}" \
  -d '{
    "medical_staff_id": 1,
    "specialty_id": 1,
    "start_date": "2025-11-20T10:00:00.000000Z",
    "end_date": "2025-11-20T11:00:00.000000Z",
    "type": "virtual",
    "reason": "Consulta de rutina",
    "urgent": false,
    "priority": 1
  }'

# Ver horarios disponibles
curl -X GET "http://127.0.0.1:8000/api/patient/appointments/available-slots?specialty_id=1&date=2025-11-20&type=presencial" \
  -H "Authorization: Bearer {patient_token}"

# Obtener enlace de teleconsulta
curl -X GET http://127.0.0.1:8000/api/patient/appointments/{appointment_id}/teleconsultation-link \
  -H "Authorization: Bearer {patient_token}"
```

#### **Gestionar Citas como Doctor**
```bash
# Login como doctor
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan.perez@saludone.com",
    "password": "password123"
  }'

# Ver mis citas
curl -X GET http://127.0.0.1:8000/api/doctor/appointments \
  -H "Authorization: Bearer {doctor_token}"

# Ver citas de hoy
curl -X GET http://127.0.0.1:8000/api/doctor/appointments/today \
  -H "Authorization: Bearer {doctor_token}"

# Programar nueva cita
curl -X POST http://127.0.0.1:8000/api/doctor/appointments/schedule \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {doctor_token}" \
  -d '{
    "patient_id": 1,
    "specialty_id": 1,
    "start_date": "2025-11-21T14:00:00.000000Z",
    "end_date": "2025-11-21T15:00:00.000000Z",
    "type": "presencial",
    "reason": "Seguimiento médico"
  }'

# Actualizar disponibilidad
curl -X PUT http://127.0.0.1:8000/api/doctor/appointments/availability \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {doctor_token}" \
  -d '{
    "day_of_week": "monday",
    "start_time": "09:00",
    "end_time": "17:00",
    "is_available": true,
    "max_appointments": 20
  }'
```

#### **Gestionar Citas como Admin**
```bash
# Login como admin
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "fernando.gil@saludone.com",
    "password": "admin123"
  }'

# Ver todas las citas
curl -X GET http://127.0.0.1:8000/api/admin/appointments \
  -H "Authorization: Bearer {admin_token}"

# Crear cita administrativa
curl -X POST http://127.0.0.1:8000/api/admin/appointments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{
    "patient_id": 1,
    "medical_staff_id": 1,
    "specialty_id": 1,
    "start_date": "2025-11-22T10:00:00.000000Z",
    "end_date": "2025-11-22T11:00:00.000000Z",
    "type": "virtual",
    "status": "programada",
    "reason": "Cita administrativa"
  }'

# Ver estadísticas
curl -X GET http://127.0.0.1:8000/api/admin/appointments/stats \
  -H "Authorization: Bearer {admin_token}"

# Sincronizar con Google Calendar
curl -X POST http://127.0.0.1:8000/api/admin/appointments/{appointment_id}/sync-google \
  -H "Authorization: Bearer {admin_token}"
```

#### **Probar Google Calendar Independiente**
```bash
# Autenticación OAuth (abrir en navegador)
# http://127.0.0.1:8000/api/calendar/auth/google

# Crear evento
curl -X POST http://127.0.0.1:8000/api/calendar/events \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{
    "summary": "Reunión de equipo",
    "description": "Reunión semanal del equipo médico",
    "start": "2025-11-25T10:00:00.000000Z",
    "end": "2025-11-25T11:00:00.000000Z",
    "attendees": ["doctor@saludone.com", "admin@saludone.com"],
    "virtual": true
  }'

# Listar eventos
curl -X GET http://127.0.0.1:8000/api/calendar/events \
  -H "Authorization: Bearer {admin_token}"

# Eliminar evento
curl -X DELETE http://127.0.0.1:8000/api/calendar/events/{event_id} \
  -H "Authorization: Bearer {admin_token}"
```

---

## Roadmap funcional

### ✅ **COMPLETADO**
- **Registro de pacientes y autenticación segura** (JWT implementado)
- **Sistema de roles y permisos** (Spatie Permission)
- **CRUD completo de usuarios** (administradores, doctores, pacientes)
- **Google OAuth** (Laravel Socialite)
- **CRUD avanzado de registros médicos** (con auditoría completa)
- **Sistema de auditoría** (log automático de cambios)
- **Gestión de archivos adjuntos** (subida, descarga, eliminación segura)
- **Validaciones médicas complejas** (signos vitales, prescripciones)
- **Permisos granulares por rol** (doctores, pacientes, administradores)
- **Soft deletes** (eliminación lógica con restauración)
- **Sistema completo de gestión de citas médicas** (CRUD para todos los roles)
- **Algoritmo inteligente de asignación automática de doctores** (con scoring)
- **Sistema de lista de espera** (para citas sin disponibilidad inmediata)
- **Integración completa con Google Calendar** (sincronización bidireccional)
- **Teleconsulta automática con Google Meet** (enlaces generados automáticamente)
- **Gestión de disponibilidad de doctores** (horarios y restricciones)
- **Recordatorios automáticos por email** (templates personalizados)
- **Controlador independiente de Google Calendar** (gestión de eventos)
- **Comandos Artisan para gestión de tokens** (renovación automática)

### 🚧 **EN PROGRESO**
- Integración EHR (FHIR) lectura/escritura
- Notificaciones push y SMS

### 📋 **PLANIFICADO**
- Asignación de citas según prioridad médica
- Facturación por sesión
- Gestión de listas de espera y redistribución
- Analítica para predecir cancelaciones y no-shows
- Tiempo real y websockets con Reverb
- Módulos clínicos avanzados (EHR/FHIR)

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
