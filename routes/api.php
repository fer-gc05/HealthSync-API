<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Doctor\MedicalRecordController as DoctorMedicalRecordController;
use App\Http\Controllers\Patient\MedicalRecordController as PatientMedicalRecordController;
use App\Http\Controllers\Admin\MedicalRecordController as AdminMedicalRecordController;
use App\Http\Controllers\Files\MedicalRecordFileController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Patient\AppointmentController as PatientAppointmentController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Patient\NotificationController as PatientNotificationController;
use App\Http\Controllers\Doctor\NotificationController as DoctorNotificationController;
use App\Http\Controllers\System\HealthController;
use App\Http\Controllers\Calendar\GoogleCalendarController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SpecialtyController;

//Route::get('/user', function (Request $request) {
//return $request->user();
//})->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Email verification routes
    Route::post('verify-email', [EmailVerificationController::class, 'verify']);
    Route::post('resend-verification', [EmailVerificationController::class, 'resend']);

    // Google OAuth routes
    Route::prefix('google')->group(function () {
        Route::get('redirect', [GoogleAuthController::class, 'redirect']);
        Route::get('callback', [GoogleAuthController::class, 'callback']);

        Route::middleware('auth:api')->group(function () {
            Route::post('link', [GoogleAuthController::class, 'link']);
            Route::delete('unlink', [GoogleAuthController::class, 'unlink']);
            Route::get('status', [GoogleAuthController::class, 'status']);
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('verification-status', [EmailVerificationController::class, 'status']);
    });
});

// Rutas para usuarios autenticados (auto-actualización)
Route::middleware('auth:api')->group(function () {
    // Actualizar perfil básico del usuario autenticado
    Route::put('profile', [UsersController::class, 'updateOwnProfile']);

    // Completar perfil de paciente para el usuario autenticado (requiere email verificado)
    Route::middleware('verified')->group(function () {
        Route::post('profile/complete/patient', [UsersController::class, 'completeOwnPatientProfile']);
        Route::put('profile/patient', [UsersController::class, 'updateOwnPatientData']);
    });
});

// Rutas administrativas protegidas por el rol de admin
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('users', [UserRoleController::class, 'users']);
    Route::get('users/role/{role}', [UserRoleController::class, 'usersByRole']);
    Route::put('users/{user}/role', [UserRoleController::class, 'assignRole']);

    // Rutas CRUD de usuarios
    Route::get('users', [UsersController::class, 'index']);
    Route::get('users/trashed', [UsersController::class, 'trashed']);
    Route::get('users/{user}', [UsersController::class, 'show']);
    Route::delete('users/{user}', [UsersController::class, 'destroy']);
    Route::post('users/{user}/restore', [UsersController::class, 'restore']);
    Route::delete('users/{user}/force', [UsersController::class, 'forceDelete']);

    // Rutas específicas por rol para crear usuarios (admin, paciente, doctor)
    Route::post('users/admin', [UsersController::class, 'storeAdmin']);
    Route::post('users/patient', [UsersController::class, 'storePatient']);
    Route::post('users/doctor', [UsersController::class, 'storeDoctor']);

    // Rutas específicas por rol para actualizar usuarios (admin, paciente, doctor)
    Route::put('users/{user}/admin', [UsersController::class, 'updateAdmin']);
    Route::put('users/{user}/patient', [UsersController::class, 'updatePatient']);
    Route::put('users/{user}/doctor', [UsersController::class, 'updateDoctor']);

    // Rutas administrativas para registros médicos
    Route::apiResource('medical-records', AdminMedicalRecordController::class);
    Route::get('medical-records/{medical_record}/audit', [AdminMedicalRecordController::class, 'audit']);

    // Notificaciones - Admin
    Route::get('notifications', [AdminNotificationController::class, 'index']);
    Route::post('notifications', [AdminNotificationController::class, 'store']);
    Route::get('notifications/{notification}', [AdminNotificationController::class, 'show'])->whereNumber('notification');
    Route::put('notifications/{notification}', [AdminNotificationController::class, 'update'])->whereNumber('notification');
    Route::delete('notifications/{notification}', [AdminNotificationController::class, 'destroy'])->whereNumber('notification');
    Route::post('notifications/{notification}/read', [AdminNotificationController::class, 'markRead'])->whereNumber('notification');
    Route::post('notifications/{notification}/unread', [AdminNotificationController::class, 'markUnread'])->whereNumber('notification');
    Route::post('notifications/send-to-role', [AdminNotificationController::class, 'sendToRole']);
    Route::post('notifications/send-to-users', [AdminNotificationController::class, 'sendToUsers']);
    Route::get('notifications/stats', [AdminNotificationController::class, 'stats']);

    // Especialidades - Admin
    Route::apiResource('specialties', SpecialtyController::class);
});

// Rutas para pacientes - registros médicos (deben ir primero para evitar conflictos)
Route::middleware(['auth:api', 'role:patient', 'verified'])->group(function () {
    Route::get('medical-records', [PatientMedicalRecordController::class, 'index']);
    Route::get('medical-records/{medical_record}', [PatientMedicalRecordController::class, 'show']);

    // Notificaciones - Paciente
    Route::get('patient/notifications', [PatientNotificationController::class, 'index']);
    Route::get('patient/notifications/{notification}', [PatientNotificationController::class, 'show'])->whereNumber('notification');
    Route::post('patient/notifications/{notification}/read', [PatientNotificationController::class, 'markRead'])->whereNumber('notification');
    Route::get('patient/notifications/unread-count', [PatientNotificationController::class, 'unreadCount']);
});

// Rutas para doctores - registros médicos
Route::middleware(['auth:api', 'role:doctor', 'verified'])->group(function () {
    Route::apiResource('medical-records', DoctorMedicalRecordController::class);
    Route::get('medical-records/patient/{patient_id}', [DoctorMedicalRecordController::class, 'patientRecords']);
    Route::get('medical-records/{medical_record}/history', [DoctorMedicalRecordController::class, 'history']);
    Route::get('medical-records/{medical_record}/audit', [DoctorMedicalRecordController::class, 'audit']);

    // Notificaciones - Doctor
    Route::get('doctor/notifications', [DoctorNotificationController::class, 'index']);
    Route::post('doctor/notifications/send-to-patient', [DoctorNotificationController::class, 'sendToPatient']);
    Route::post('doctor/notifications/appointment-reminder', [DoctorNotificationController::class, 'appointmentReminder']);
    Route::post('doctor/notifications/{notification}/read', [DoctorNotificationController::class, 'markRead'])->whereNumber('notification');
    Route::get('doctor/notifications/unread-count', [DoctorNotificationController::class, 'unreadCount']);
});

// Rutas de archivos para registros médicos (doctores y admin)
Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::post('medical-records/{medical_record}/files', [MedicalRecordFileController::class, 'store']);
    Route::get('medical-records/{medical_record}/files', [MedicalRecordFileController::class, 'index']);
    Route::get('medical-records/{medical_record}/files/{file_id}', [MedicalRecordFileController::class, 'download']);
    Route::delete('medical-records/{medical_record}/files/{file_id}', [MedicalRecordFileController::class, 'destroy']);
});

Route::prefix('calendar')->group(function () {
    Route::get('auth/google', [GoogleCalendarController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback']);

    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::get('events', [GoogleCalendarController::class, 'showEvents']);
        Route::post('events', [GoogleCalendarController::class, 'storeEvent']);
        Route::put('events/{eventId}', [GoogleCalendarController::class, 'updateEvent']);
        Route::delete('events/{eventId}', [GoogleCalendarController::class, 'destroyEvent']);
    });

});

// Rutas de citas para administradores
Route::middleware(['auth:api', 'role:admin', 'verified'])->prefix('admin')->group(function () {
    Route::apiResource('appointments', AdminAppointmentController::class);
    Route::get('appointments/stats', [AdminAppointmentController::class, 'stats']);
    Route::get('appointments/availability', [AdminAppointmentController::class, 'availability']);
    Route::post('appointments/{appointment}/sync-google', [AdminAppointmentController::class, 'syncWithGoogle']);
    Route::post('appointments/assign-optimal', [AdminAppointmentController::class, 'assignOptimal']);
});

// Rutas de citas para doctores
Route::middleware(['auth:api', 'role:doctor', 'verified'])->prefix('doctor')->group(function () {
    Route::get('appointments', [DoctorAppointmentController::class, 'index']);
    Route::get('appointments/today', [DoctorAppointmentController::class, 'today']);
    Route::get('appointments/this-week', [DoctorAppointmentController::class, 'thisWeek']);
    Route::get('appointments/availability', [DoctorAppointmentController::class, 'availability']);
    Route::get('appointments/waitlist', [DoctorAppointmentController::class, 'waitlist']);
    Route::post('appointments/schedule', [DoctorAppointmentController::class, 'schedule']);
    Route::put('appointments/availability', [DoctorAppointmentController::class, 'updateAvailability']);
    Route::get('appointments/{appointment}', [DoctorAppointmentController::class, 'show']);
    Route::put('appointments/{appointment}', [DoctorAppointmentController::class, 'update']);
    Route::post('appointments/{appointment}/start-teleconsultation', [DoctorAppointmentController::class, 'startTeleconsultation']);
    Route::post('appointments/{appointment}/end-teleconsultation', [DoctorAppointmentController::class, 'endTeleconsultation']);
});

// Rutas de citas para pacientes
Route::middleware(['auth:api', 'role:patient', 'verified'])->prefix('patient')->group(function () {
    Route::get('appointments', [PatientAppointmentController::class, 'index']);
    Route::post('appointments/book', [PatientAppointmentController::class, 'book']);
    Route::put('appointments/{appointment}/reschedule', [PatientAppointmentController::class, 'reschedule']);
    Route::post('appointments/{appointment}/cancel', [PatientAppointmentController::class, 'cancel']);
    Route::get('appointments/{appointment}/teleconsultation-link', [PatientAppointmentController::class, 'getTeleconsultationLink']);
    Route::get('appointments/available-slots', [PatientAppointmentController::class, 'availableSlots']);
    Route::get('appointments/available-doctors', [PatientAppointmentController::class, 'availableDoctors']);
    Route::get('appointments/upcoming', [PatientAppointmentController::class, 'upcoming']);
    Route::get('appointments/history', [PatientAppointmentController::class, 'history']);
});

// Health check endpoint
Route::get('/health', [HealthController::class, 'index']);
