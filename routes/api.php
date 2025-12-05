<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
/*
|--------------------------------------------------------------------------
| API Routes V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // --- Rutas Públicas (No requieren Token) ---
    Route::post('/login', [AuthController::class, 'login']);

    // --- Rutas Protegidas (Requieren Token Bearer) ---
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']); // Para verificar usuario al recargar Vue

        // Actualización de perfil
        Route::put('/profile/update', [UserController::class, 'updateProfile']);

        // Aquí irán tus futuras rutas administrativas:
        // Route::apiResource('estudiantes', StudentController::class);
        // Route::apiResource('profesores', TeacherController::class);
    });

});
