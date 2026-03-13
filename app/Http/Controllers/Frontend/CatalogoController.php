<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Encabezado;
use App\Models\Colegio;

class CatalogoController extends Controller
{
    /**
     * Devuelve toda la información necesaria para el Catalogo.vue público.
     */
    public function indexPublic(Request $request)
    {
        // 1. Obtener el encabezado de la sección específica
        $encabezado = Encabezado::where('seccion', 'catalogo')->first();

        // Si no existe, mandar algo por defecto (opcional)
        if (!$encabezado) {
            $encabezado = [
                'titulo' => 'Nuestra Oferta Educativa',
                'subtitulo' => 'Excelencia Académica',
                'descripcion' => 'Explora los programas especializados de nuestras instituciones.'
            ];
        }

        // 2. Obtener los colegios con sus respectivas carreras web
        $colegios = Colegio::with('catalogoCarreras')->get();

        // 3. Formatear la data como lo espera el frontend
        // Note: The frontend expects institutions as an array, we map it to match the vue requirements
        $institutions = $colegios->map(function ($col) {
            return [
                'id' => 'col_' . $col->id,
                'name' => $col->nombre,
                'label' => $col->label ?? 'Categoría',
                'description' => $col->descripcion_web ?? 'Descripción del colegio',
                'theme' => $col->theme ?? 'blue', // default if not set
                'careers' => $col->catalogoCarreras->map(function ($car) {
                    return [
                        'id' => $car->id,
                        'title' => $car->nombre,
                        'subtitle' => $car->jornada,
                        'schedule' => $car->jornada,
                        'badge' => $car->badge,
                        'icon' => $car->icon ?? 'graduation-cap',
                        'features' => $car->detalles ?? [],
                    ];
                })
            ];
        });

        return response()->json([
            'encabezado' => $encabezado,
            'institutions' => $institutions
        ]);
    }
    /**
     * Devuelve la información para el administrador (AdminCatalogo.vue)
     */
    public function getAdminData()
    {
        $encabezado = Encabezado::where('seccion', 'catalogo')->first();
        // Cargar colegios junto con sus carreras web asociadas
        $colegios = Colegio::with('catalogoCarreras')->get();

        return response()->json([
            'encabezado' => $encabezado,
            'institutions' => $colegios
        ]);
    }

    /**
     * Guarda o actualiza el encabezado de forma dinámica
     */
    public function saveEncabezado(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string'
        ]);

        $encabezado = Encabezado::updateOrCreate(
            ['seccion' => 'catalogo'],
            [
                'titulo' => $request->titulo,
                'subtitulo' => $request->subtitulo,
                'descripcion' => $request->descripcion
            ]
        );

        Cache::forget('public_catalogo');

        return response()->json(['message' => 'Encabezado guardado correctamente', 'encabezado' => $encabezado]);
    }

    /**
     * Actualiza la información visual de un colegio (Categoría del Catálogo)
     */
    public function updateColegio(Request $request, $id)
    {
        $colegio = Colegio::findOrFail($id);

        $request->validate([
            'label' => 'nullable|string|max:255',
            'descripcion_web' => 'nullable|string',
            'theme' => 'nullable|string|max:50'
        ]);

        $colegio->update([
            'label' => $request->label,
            'descripcion_web' => $request->descripcion_web,
            'theme' => $request->theme
        ]);

        Cache::forget('public_catalogo');

        return response()->json(['message' => 'Información visual del colegio actualizada', 'colegio' => $colegio]);
    }

    /**
     * Crea una nueva carrera en un colegio específico (AdminCatalogo.vue)
     */
    public function storeCarrera(Request $request)
    {
        $request->validate([
            'colegio_id' => 'required|exists:colegios,id',
            'nombre' => 'required|string|max:255',
            'jornada' => 'required|string|max:255',
            'detalles' => 'nullable|array',
            'badge' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
        ]);

        $carrera = \App\Models\CatalogoCarrera::create($request->all());

        Cache::forget('public_catalogo');

        return response()->json(['message' => 'Carrera creada exitosamente.', 'carrera' => $carrera], 201);
    }

    /**
     * Actualiza una carrera existente
     */
    public function updateCarrera(Request $request, $id)
    {
        $carrera = \App\Models\CatalogoCarrera::findOrFail($id);

        $request->validate([
            'colegio_id' => 'required|exists:colegios,id',
            'nombre' => 'required|string|max:255',
            'jornada' => 'required|string|max:255',
            'detalles' => 'nullable|array',
            'badge' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
        ]);

        $carrera->update($request->all());

        Cache::forget('public_catalogo');

        return response()->json(['message' => 'Carrera actualizada exitosamente.', 'carrera' => $carrera]);
    }

    /**
     * Elimina una carrera del catálogo
     */
    public function destroyCarrera($id)
    {
        $carrera = \App\Models\CatalogoCarrera::findOrFail($id);
        $carrera->delete();

        Cache::forget('public_catalogo');

        return response()->json(['message' => 'Carrera eliminada exitosamente.']);
    }
}
