<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SliderController;
use App\Http\Controllers\Modulo1\ColegioController;
use App\Http\Controllers\Modulo1\NivelController;
use App\Http\Controllers\Modulo1\GradoController;
use App\Http\Controllers\Modulo1\CicloController;
use App\Http\Controllers\Modulo2\EstudianteController;
use App\Http\Controllers\Modulo2\InscripcionController;
/*
|--------------------------------------------------------------------------
| API Routes V1
|--------------------------------------------------------------------------
*/



Route::prefix('alianza')->group(function () {

    // --- Rutas Públicas (No requieren Token) ---
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/slider', [SliderController::class, 'index']);

    // --- Rutas Protegidas (Requieren Token Bearer) ---
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']); // Para verificar usuario al recargar Vue

        // Actualización de perfil
        Route::put('/profile/update', [UserController::class, 'updateProfile']);

                // Actualizar textos generales
        Route::post('/slider/settings', [SliderController::class, 'storeSetting']);
        Route::put('/slider/settings/{id}', [SliderController::class, 'updateSetting']);
        Route::delete('/slider/settings/{id}', [SliderController::class, 'destroySetting']);

        // Subir imagen
        Route::post('/slider/images', [SliderController::class, 'storeImage']);

        // Eliminar imagen
        Route::delete('/slider/images/{id}', [SliderController::class, 'destroyImage']);

        // Aquí irán tus futuras rutas administrativas:
        // Route::apiResource('estudiantes', StudentController::class);
        // Route::apiResource('profesores', TeacherController::class);

        // --- Módulo 1: Estructura Académica (Colegios, Niveles, Grados, Ciclos) ---
        Route::apiResource('colegios', ColegioController::class);
        Route::apiResource('niveles', NivelController::class);
        Route::apiResource('grados', GradoController::class);
        Route::apiResource('ciclos', CicloController::class);


        // --- Módulo 2: Estudiantes e Historial ---
        Route::apiResource('estudiantes', EstudianteController::class);
        Route::apiResource('inscripciones', InscripcionController::class);
    });

});
