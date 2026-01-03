<?php

namespace App\Http\Controllers\Modulo1;

use App\Http\Controllers\Controller;
use App\Models\Grado;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    public function index()
    {
        return response()->json(Grado::with('nivel')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nivel_id' => 'required|exists:niveles,id', // Validamos rango lógico
            'nombre' => 'required|string|max:255',
        ]);

        $grado = Grado::create($validated);

        return response()->json($grado, 201);
    }

    public function show($id)
    {
        $grado = Grado::with('nivel')->find($id);

        if (!$grado) {
            return response()->json(['message' => 'Grado no encontrado'], 404);
        }

        return response()->json($grado);
    }

    public function update(Request $request, $id)
    {
        $grado = Grado::find($id);

        if (!$grado) {
            return response()->json(['message' => 'Grado no encontrado'], 404);
        }

        $validated = $request->validate([
            'nivel_id' => 'sometimes|required|exists:niveles,id',
            'nombre' => 'sometimes|required|string|max:255',
        ]);

        $grado->update($validated);

        return response()->json($grado);
    }

    public function destroy($id)
    {
        $grado = Grado::find($id);

        if (!$grado) {
            return response()->json(['message' => 'Grado no encontrado'], 404);
        }

        $grado->delete();

        return response()->json(['message' => 'Grado eliminado']);
    }
}
