<?php

namespace App\Http\Controllers\Modulo1;

use App\Http\Controllers\Controller;
use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelController extends Controller
{
    public function index()
    {
        return response()->json(Nivel::with(['colegio', 'grados'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'colegio_id' => 'required|exists:colegios,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
        ]);

        $nivel = Nivel::create($validated);

        return response()->json($nivel, 201);
    }

    public function show($id)
    {
        $nivel = Nivel::with(['colegio', 'grados'])->find($id);

        if (!$nivel) {
            return response()->json(['message' => 'Nivel no encontrado'], 404);
        }

        return response()->json($nivel);
    }

    public function update(Request $request, $id)
    {
        $nivel = Nivel::find($id);

        if (!$nivel) {
            return response()->json(['message' => 'Nivel no encontrado'], 404);
        }

        $validated = $request->validate([
            'colegio_id' => 'sometimes|required|exists:colegios,id',
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'sometimes|required|string|max:255',
        ]);

        $nivel->update($validated);

        return response()->json($nivel);
    }

    public function destroy($id)
    {
        $nivel = Nivel::find($id);

        if (!$nivel) {
            return response()->json(['message' => 'Nivel no encontrado'], 404);
        }

        $nivel->delete();

        return response()->json(['message' => 'Nivel eliminado']);
    }
}
