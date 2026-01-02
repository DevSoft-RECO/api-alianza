<?php

namespace App\Http\Controllers\Modulo1;

use App\Http\Controllers\Controller;
use App\Models\Colegio;
use Illuminate\Http\Request;

class ColegioController extends Controller
{
    public function index()
    {
        return response()->json(Colegio::with('niveles')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'director' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
        ]);

        $colegio = Colegio::create($validated);

        return response()->json($colegio, 201);
    }

    public function show($id)
    {
        $colegio = Colegio::with('niveles')->find($id);

        if (!$colegio) {
            return response()->json(['message' => 'Colegio no encontrado'], 404);
        }

        return response()->json($colegio);
    }

    public function update(Request $request, $id)
    {
        $colegio = Colegio::find($id);

        if (!$colegio) {
            return response()->json(['message' => 'Colegio no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'director' => 'sometimes|required|string|max:255',
            'direccion' => 'sometimes|required|string|max:255',
        ]);

        $colegio->update($validated);

        return response()->json($colegio);
    }

    public function destroy($id)
    {
        $colegio = Colegio::find($id);

        if (!$colegio) {
            return response()->json(['message' => 'Colegio no encontrado'], 404);
        }

        $colegio->delete();

        return response()->json(['message' => 'Colegio eliminado']);
    }
}
