<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserRoleController;
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

// Admin routes - Protected by admin role and specific permissions
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('users', [UserRoleController::class, 'users']);
    Route::get('users/role/{role}', [UserRoleController::class, 'usersByRole']);
    Route::put('users/{user}/role', [UserRoleController::class, 'assignRole']);
});
