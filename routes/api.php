<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
    //return $request->user();
//})->middleware('auth:sanctum');


Route::prefix('auth')->group(function (){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class,'login']);

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
});
