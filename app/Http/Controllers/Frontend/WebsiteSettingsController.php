<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteSettingsController extends Controller
{
    /**
     * Obtener todas las configuraciones del sitio.
     */
    public function index()
    {
        // Retornar como { 'clave': 'valor', ... } para fácil uso en frontend
        $settings = DB::table('configuraciones_sitio')->pluck('valor', 'clave');
        return response()->json($settings);
    }

    /**
     * Actualizar configuraciones del sitio.
     * Espera un request como { 'footer_address': 'Nueva dirección', ... }
     */
    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            // Actualizar o crear
            // IMPORTANTE: Ya no forzamos etiquetas ni valores por defecto.
            // Si el valor es null, se guarda como null.
            DB::table('configuraciones_sitio')->updateOrInsert(
                ['clave' => $key],
                [
                    'valor' => $value,
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json([
            'message' => 'Configuraciones actualizadas correctamente',
            'settings' => DB::table('configuraciones_sitio')->pluck('valor', 'clave')
        ]);
    }
}
