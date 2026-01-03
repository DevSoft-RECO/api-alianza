<?php

namespace App\Http\Controllers\Modulo2;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    // Optimización: Paginación para no sobrecargar el frontend y búsqueda eficiente
    public function index(Request $request)
    {
        $query = Estudiante::query();

        // Búsqueda por carnet, nombre o apellido (utilizando índices)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('codigo_estudiante', 'like', "%{$search}%")
                  ->orWhere('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%");
        }

        // Paginación estándar de 20 elementos, configurable
        $perPage = $request->input('per_page', 20);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_estudiante' => 'required|string|unique:estudiantes,codigo_estudiante|max:255',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'nombre_encargado' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
        ]);

        $estudiante = Estudiante::create($validated);

        return response()->json($estudiante, 201);
    }

    public function show($id)
    {
        $estudiante = Estudiante::with('inscripciones.ciclo', 'inscripciones.grado')->find($id);

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        return response()->json($estudiante);
    }

    public function update(Request $request, $id)
    {
        $estudiante = Estudiante::find($id);

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        $validated = $request->validate([
            'codigo_estudiante' => 'sometimes|required|string|max:255|unique:estudiantes,codigo_estudiante,' . $id,
            'nombres' => 'sometimes|required|string|max:255',
            'apellidos' => 'sometimes|required|string|max:255',
            'nombre_encargado' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
        ]);

        $estudiante->update($validated);

        return response()->json($estudiante);
    }

    public function destroy($id)
    {
        $estudiante = Estudiante::find($id);

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        $estudiante->delete();

        return response()->json(['message' => 'Estudiante eliminado']);
    }
}
