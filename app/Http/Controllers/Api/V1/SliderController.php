<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File; // Necesario para manejar archivos sin Storage
use Illuminate\Support\Facades\Validator;

class SliderController extends Controller
{
    /**
     * Obtener configuración e imágenes.
     */
    public function index()
    {
        $settings = DB::table('slider_settings')->orderBy('id', 'asc')->get();
        $images = DB::table('slider_images')->orderBy('created_at', 'desc')->get();

        $mappedImages = $images->map(function ($img) {
            return [
                'id' => $img->id,
                // Al estar en public/, asset() genera la URL directa sin storage link
                'url' => asset($img->image_path), 
                'category' => $img->category,
            ];
        });

        return response()->json([
            'settings' => $settings,
            'images' => $mappedImages
        ]);
    }

    /**
     * Crear un nuevo mensaje (Título/Subtítulo)
     */
    public function storeSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'main_title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $id = DB::table('slider_settings')->insertGetId([
            'main_title' => $request->main_title,
            'subtitle' => $request->subtitle,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Mensaje creado.', 'data' => ['id' => $id, ...$request->all()]]);
    }

    /**
     * Actualizar un mensaje específico
     */
    public function updateSetting(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'main_title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::table('slider_settings')->where('id', $id)->update([
            'main_title' => $request->main_title,
            'subtitle' => $request->subtitle,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Mensaje actualizado.']);
    }

    /**
     * Eliminar un mensaje
     */
    public function destroySetting($id)
    {
        // Evitar dejar la tabla vacía si lo deseas, o permitirlo.
        // Aquí permitimos borrar cualquiera.
        DB::table('slider_settings')->where('id', $id)->delete();
        return response()->json(['message' => 'Mensaje eliminado.']);
    }

    /**
     * Subir imagen a public/uploads/slider.
     */
    public function storeImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'category' => 'nullable|in:EPAE,CCI,LEDNA',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Definir carpeta destino dentro de public/
            $destinationPath = public_path('uploads/slider');
            
            // Asegurarse de que el directorio existe, si no, crearlo
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            // Nombre único para el archivo
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Mover el archivo físicamente a public/uploads/slider
            $file->move($destinationPath, $filename);

            // Guardar la ruta relativa para usar con asset() luego
            $dbPath = 'uploads/slider/' . $filename;

            $id = DB::table('slider_images')->insertGetId([
                'image_path' => $dbPath,
                'category' => $request->category, // Puede ser null
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Imagen subida correctamente.',
                'image' => [
                    'id' => $id,
                    'url' => asset($dbPath),
                    'category' => $request->category
                ]
            ], 201);
        }

        return response()->json(['message' => 'Error al subir archivo.'], 400);
    }

    /**
     * Eliminar imagen y archivo físico.
     */
    public function destroyImage($id)
    {
        $image = DB::table('slider_images')->where('id', $id)->first();

        if (!$image) {
            return response()->json(['message' => 'Imagen no encontrada.'], 404);
        }

        // Construir la ruta completa del sistema de archivos
        $fullPath = public_path($image->image_path);

        // Eliminar el archivo físico si existe
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        // Eliminar registro
        DB::table('slider_images')->where('id', $id)->delete();

        return response()->json(['message' => 'Imagen eliminada correctamente.']);
    }
}