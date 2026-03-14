<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SliderController;
use App\Http\Controllers\Frontend\CatalogoController;
use App\Http\Controllers\Modulo1\ColegioController;
use App\Http\Controllers\Modulo1\NivelController;
use App\Http\Controllers\Modulo1\GradoController;
use App\Http\Controllers\Modulo1\CicloController;
use App\Http\Controllers\Modulo2\EstudianteController;
use App\Http\Controllers\Modulo2\InscripcionController;
use App\Http\Controllers\Api\FinanzasController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Frontend\WebsiteSettingsController;
use App\Http\Controllers\Frontend\GaleriaController;
use App\Http\Controllers\Frontend\PublicDataController;


/*
|--------------------------------------------------------------------------
| API Routes V1
|--------------------------------------------------------------------------
*/



Route::prefix('alianza')->group(function () {

    // --- Rutas Públicas (No requieren Token) ---
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/slider', [SliderController::class, 'index']);
    Route::get('/catalogo', [CatalogoController::class, 'indexPublic']);
    Route::get('/nosotros', [\App\Http\Controllers\Frontend\NosotrosController::class, 'indexPublic']);
    Route::get('/website/settings', [WebsiteSettingsController::class, 'index']);
    Route::get('/galeria', [GaleriaController::class, 'index']);
    Route::get('/public/init', [PublicDataController::class, 'init']);

    // --- Rutas Protegidas (Requieren Token Bearer) ---
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']); 
        
        // --- Dashboard Administrativo ---
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // Actualización de perfil
        Route::put('/profile/update', [UserController::class, 'updateProfile']);

                // Actualizar textos generales
        Route::post('/slider/settings', [SliderController::class, 'storeSetting']);
        Route::put('/slider/settings/{id}', [SliderController::class, 'updateSetting']);
        Route::delete('/slider/settings/{id}', [SliderController::class, 'destroySetting']);

        // --- Configuraciones del Sitio (Footer, etc.) ---
        Route::put('/website/settings', [App\Http\Controllers\Frontend\WebsiteSettingsController::class, 'update']);

        // --- Catalogo Web Admin ---
        Route::get('/admin/catalogo', [CatalogoController::class, 'getAdminData']);
        Route::post('/admin/catalogo/encabezado', [CatalogoController::class, 'saveEncabezado']);
        Route::put('/admin/catalogo/colegio/{id}', [CatalogoController::class, 'updateColegio']);

        Route::post('/admin/catalogo/carrera', [CatalogoController::class, 'storeCarrera']);
        Route::put('/admin/catalogo/carrera/{id}', [CatalogoController::class, 'updateCarrera']);
        Route::delete('/admin/catalogo/carrera/{id}', [CatalogoController::class, 'destroyCarrera']);

        // --- Nosotros / Contactanos Web Admin ---
        Route::get('/admin/nosotros', [\App\Http\Controllers\Frontend\NosotrosController::class, 'getAdminData']);
        Route::post('/admin/nosotros/encabezado', [\App\Http\Controllers\Frontend\NosotrosController::class, 'saveEncabezado']);

        // Unified CRUD for Colegios History and Fundadores
        Route::post('/admin/nosotros/registros', [\App\Http\Controllers\Frontend\NosotrosController::class, 'storeRegistro']);
        Route::put('/admin/nosotros/registros/{id}', [\App\Http\Controllers\Frontend\NosotrosController::class, 'updateRegistro']); // Spoofed PUT to handle formdata files
        Route::delete('/admin/nosotros/registros/{id}', [\App\Http\Controllers\Frontend\NosotrosController::class, 'destroyRegistro']);

        // --- Galería Web Admin ---
        Route::get('/admin/galeria', [GaleriaController::class, 'index']);
        Route::post('/admin/galeria', [GaleriaController::class, 'store']);
        Route::put('/admin/galeria/{id}', [GaleriaController::class, 'update']);
        Route::delete('/admin/galeria/{id}', [GaleriaController::class, 'destroy']);

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

        // --- Módulo 3: Financiero (V3 FINAL) ---

        // 1. Configuración (FinanzasController)
        Route::prefix('finanzas')->group(function() {
            Route::get('conceptos', [FinanzasController::class, 'indexConceptos']);
            Route::post('conceptos', [FinanzasController::class, 'storeConcepto']);
            Route::put('conceptos/{id}', [FinanzasController::class, 'updateConcepto']);
            Route::delete('conceptos/{id}', [FinanzasController::class, 'destroyConcepto']);

            Route::get('precios', [FinanzasController::class, 'indexPrecios']); // ?colegio_id=1
            Route::post('precios', [FinanzasController::class, 'storePrecio']);
            Route::put('precios/{id}', [FinanzasController::class, 'updatePrecio']);
            Route::delete('precios/{id}', [FinanzasController::class, 'destroyPrecio']);

            Route::get('asignaciones', [FinanzasController::class, 'indexAsignaciones']); // ?grado_id=1
            Route::post('asignaciones', [FinanzasController::class, 'storeAsignacion']);
            Route::delete('asignaciones/{id}', [FinanzasController::class, 'destroyAsignacion']);

            Route::post('precios/ajuste-masivo', [FinanzasController::class, 'ajusteMasivo']);
            Route::delete('cargos/eliminar-masivo', [FinanzasController::class, 'eliminarCargosMasivo']);
        });

        Route::get('/user', [AuthController::class, 'me']); // Alias for compatibility

        // ... existing routes ...

        // 2. Caja y Pagos (CajaController)
        Route::prefix('caja')->group(function() {
            Route::get('pagos', [CajaController::class, 'index']); // Historial
            Route::get('pagos/{id}', [CajaController::class, 'show']); // <--- Detalle de Pago (Recibo)
            Route::get('estado-cuenta/{estudiante_id}', [CajaController::class, 'estadoCuenta']);
            Route::post('pagar', [CajaController::class, 'procesarPago']);
            Route::patch('pagos/{id}/fecha', [CajaController::class, 'updateFecha']);
        });
    });

});
