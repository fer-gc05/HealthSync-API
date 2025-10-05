<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Doctor\MedicalRecordController as DoctorMedicalRecordController;
use App\Http\Controllers\Patient\MedicalRecordController as PatientMedicalRecordController;
use App\Http\Controllers\Admin\MedicalRecordController as AdminMedicalRecordController;
use App\Http\Controllers\MedicalRecordFileController;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
    //return $request->user();
//})->middleware('auth:sanctum');


Route::prefix('auth')->group(function (){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class,'login']);

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

    Route::middleware('auth:api')->group(function (){
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// Rutas para usuarios autenticados (auto-actualización)
Route::middleware('auth:api')->group(function () {
    // Actualizar perfil básico del usuario autenticado
    Route::put('profile', [UsersController::class, 'updateOwnProfile']);
    
    // Completar perfil de paciente para el usuario autenticado
    Route::post('profile/complete/patient', [UsersController::class, 'completeOwnPatientProfile']);
    
    // Actualizar datos específicos de paciente para el usuario autenticado
    Route::put('profile/patient', [UsersController::class, 'updateOwnPatientData']);
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
});

// Rutas para pacientes - registros médicos (deben ir primero para evitar conflictos)
Route::middleware(['auth:api', 'role:patient'])->group(function () {
    Route::get('medical-records', [PatientMedicalRecordController::class, 'index']);
    Route::get('medical-records/{medical_record}', [PatientMedicalRecordController::class, 'show']);
});

// Rutas para doctores - registros médicos
Route::middleware(['auth:api', 'role:doctor'])->group(function () {
    Route::apiResource('medical-records', DoctorMedicalRecordController::class);
    Route::get('medical-records/patient/{patient_id}', [DoctorMedicalRecordController::class, 'patientRecords']);
    Route::get('medical-records/{medical_record}/history', [DoctorMedicalRecordController::class, 'history']);
    Route::get('medical-records/{medical_record}/audit', [DoctorMedicalRecordController::class, 'audit']);
});

// Rutas de archivos para registros médicos (doctores y admin)
Route::middleware(['auth:api'])->group(function () {
    Route::post('medical-records/{medical_record}/files', [MedicalRecordFileController::class, 'store']);
    Route::get('medical-records/{medical_record}/files', [MedicalRecordFileController::class, 'index']);
    Route::get('medical-records/{medical_record}/files/{file_id}', [MedicalRecordFileController::class, 'download']);
    Route::delete('medical-records/{medical_record}/files/{file_id}', [MedicalRecordFileController::class, 'destroy']);
});
