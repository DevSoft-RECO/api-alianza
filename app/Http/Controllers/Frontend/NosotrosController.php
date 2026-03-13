<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Encabezado;
use App\Models\SobreNosotros;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class NosotrosController extends Controller
{
    /**
     * Data pública para Contactanos.vue
     */
    public function indexPublic(Request $request)
    {
        $encabezado = Encabezado::where('seccion', 'nosotros')->first();

        // Valores por defecto
        if (!$encabezado) {
            $encabezado = [
                'titulo' => 'Contáctanos',
                'subtitulo' => 'Atención Personalizada',
                'descripcion' => 'Estamos aquí para responder todas tus preguntas y brindarte la información que necesitas sobre nuestros programas educativos.'
            ];
        }

        // Obtener toda la información y agruparla por tipo
        $registros = SobreNosotros::all()->groupBy('tipo');

        $colegios = $registros->has('colegio') ? $registros['colegio'] : [];
        $fundadores = $registros->has('fundador') ? $registros['fundador'] : [];

        return response()->json([
            'encabezado' => $encabezado,
            'colegios' => $colegios,
            'fundadores' => $fundadores
        ]);
    }

    /**
     * Data para el administrador AdminNosotros.vue
     */
    public function getAdminData()
    {
        $encabezado = Encabezado::where('seccion', 'nosotros')->first();
        $registros = SobreNosotros::all();

        return response()->json([
            'encabezado' => $encabezado,
            'registros' => $registros
        ]);
    }

    /**
     * Guardar/Actualizar el Encabezado
     */
    public function saveEncabezado(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string'
        ]);

        $encabezado = Encabezado::updateOrCreate(
            ['seccion' => 'nosotros'],
            [
                'titulo' => $request->titulo,
                'subtitulo' => $request->subtitulo,
                'descripcion' => $request->descripcion
            ]
        );

        Cache::forget('public_nosotros');

        return response()->json(['message' => 'Encabezado guardado correctamente', 'encabezado' => $encabezado]);
    }

    /**
     * Crear un nuevo registro (Colegio o Fundador)
     */
    public function storeRegistro(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:colegio,fundador',
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/nosotros');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $data['foto'] = 'uploads/nosotros/' . $filename;
        }

        $registro = SobreNosotros::create($data);

        Cache::forget('public_nosotros');

        return response()->json(['message' => 'Registro creado exitosamente.', 'registro' => $registro], 201);
    }

    /**
     * Actualizar un registro (Colegio o Fundador)
     */
    public function updateRegistro(Request $request, $id)
    {
        $registro = SobreNosotros::findOrFail($id);

        $request->validate([
            'tipo' => 'required|in:colegio,fundador',
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            if ($registro->foto && File::exists(public_path($registro->foto))) {
                File::delete(public_path($registro->foto));
            }

            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/nosotros');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $data['foto'] = 'uploads/nosotros/' . $filename;
        }

        $registro->update($data);

        Cache::forget('public_nosotros');

        return response()->json(['message' => 'Registro actualizado exitosamente.', 'registro' => $registro]);
    }

    /**
     * Eliminar un registro físicamente y su foto
     */
    public function destroyRegistro($id)
    {
        $registro = SobreNosotros::findOrFail($id);

        if ($registro->foto && File::exists(public_path($registro->foto))) {
            File::delete(public_path($registro->foto));
        }

        $registro->delete();

        Cache::forget('public_nosotros');

        return response()->json(['message' => 'Registro eliminado exitosamente.']);
    }
}
