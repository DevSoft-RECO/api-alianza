<?php

namespace App\Http\Controllers\Modulo1;

use App\Http\Controllers\Controller;
use App\Models\Ciclo;
use Illuminate\Http\Request;

class CicloController extends Controller
{
    public function index()
    {
        return response()->json(Ciclo::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'anio' => 'required|integer',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'boolean',
        ]);

        $ciclo = Ciclo::create($validated);

        return response()->json($ciclo, 201);
    }

    public function show($id)
    {
        $ciclo = Ciclo::find($id);

        if (!$ciclo) {
            return response()->json(['message' => 'Ciclo no encontrado'], 404);
        }

        return response()->json($ciclo);
    }

    public function update(Request $request, $id)
    {
        $ciclo = Ciclo::find($id);

        if (!$ciclo) {
            return response()->json(['message' => 'Ciclo no encontrado'], 404);
        }

        $validated = $request->validate([
            'anio' => 'sometimes|required|integer',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after:fecha_inicio',
            'activo' => 'boolean',
        ]);

        $ciclo->update($validated);

        return response()->json($ciclo);
    }

    public function destroy($id)
    {
        $ciclo = Ciclo::find($id);

        if (!$ciclo) {
            return response()->json(['message' => 'Ciclo no encontrado'], 404);
        }

        $ciclo->delete();

        return response()->json(['message' => 'Ciclo eliminado']);
    }
}
