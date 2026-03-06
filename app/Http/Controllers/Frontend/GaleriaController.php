<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\GaleriaImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GaleriaController extends Controller
{
    public function index()
    {
        return GaleriaImagen::orderBy('orden')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'descripcion' => 'nullable|string|max:255',
            'orden' => 'nullable|integer'
        ]);

        $item = new GaleriaImagen();
        $item->descripcion = $request->descripcion;
        $item->orden = $request->orden ?? 0;

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = public_path('uploads/galeria');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $file->move($path, $filename);
            $item->imagen = '/uploads/galeria/' . $filename;
        }

        $item->save();

        return response()->json($item, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'descripcion' => 'nullable|string|max:255',
            'orden' => 'nullable|integer'
        ]);

        $item = GaleriaImagen::findOrFail($id);
        $item->descripcion = $request->descripcion;
        $item->orden = $request->orden ?? $item->orden;
        $item->save();

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = GaleriaImagen::findOrFail($id);

        $filePath = public_path($item->imagen);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $item->delete();

        return response()->json(['message' => 'Imagen eliminada correctamente']);
    }
}
