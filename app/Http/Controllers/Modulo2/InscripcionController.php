<?php

namespace App\Http\Controllers\Modulo2;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Grado;
use App\Models\Nivel;
use App\Models\Colegio;
use App\Models\Ciclo;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
public function index(Request $request)
{
    $query = Inscripcion::with([
        'estudiante:id,apellidos,nombres,codigo_estudiante',
        'grado:id,nombre,nivel_id',
        'grado.nivel:id,nombre',
        'colegio:id,nombre',
        'ciclo:id,anio'
    ]);

    if ($request->filled('ciclo_id')) {
        $query->where('ciclo_id', $request->ciclo_id);
    }

    if ($request->filled('colegio_id')) {
        $query->where('colegio_id', $request->colegio_id);
    }

    if ($request->filled('nivel_id')) {
        $query->whereHas('grado', function ($q) use ($request) {
            $q->where('nivel_id', $request->nivel_id);
        });
    }

    if ($request->filled('grado_id')) {
        $query->where('grado_id', $request->grado_id); // ✅ ahora sí
    }

    if ($request->filled('estado')) {
        $query->where('estado', $request->estado);
    }

    return $query->paginate(10);
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'estudiante_id' => [
                'required',
                'exists:estudiantes,id',
                // Validación compuesta: No permitir mismo estudiante en mismo ciclo Y mismo grado
                function ($attribute, $value, $fail) use ($request) {
                    $cicloId = $request->input('ciclo_id');
                    $gradoId = $request->input('grado_id');
                    if ($cicloId && $gradoId) {
                        // 1. Verificar duplicidad exacta (Estudiante + Ciclo + Grado)
                        $existe = Inscripcion::where('estudiante_id', $value)
                                             ->where('ciclo_id', $cicloId)
                                             ->where('grado_id', $gradoId)
                                             ->exists();
                        if ($existe) {
                            $fail('El estudiante ya está inscrito en este grado para el ciclo seleccionado.');
                            return;
                        }

                        // 2. Verificar límite máximo de 2 inscripciones anuales
                        // (Por compatibilidad de horarios: Diario + Fin de Semana, etc.)
                        $cantidad = Inscripcion::where('estudiante_id', $value)
                                               ->where('ciclo_id', $cicloId)
                                               ->count();
                        if ($cantidad >= 2) {
                            $fail('El estudiante ya alcanzó el límite máximo de 2 inscripciones para este ciclo.');
                        }
                    }
                }
            ],
            'ciclo_id' => 'required|exists:ciclos,id',
            'colegio_id' => 'required|exists:colegios,id',
            'grado_id' => [
                'required',
                'exists:grados,id',
                function ($attribute, $value, $fail) use ($request) {
                    $colegioId = $request->input('colegio_id');
                    if ($colegioId) {
                        $grado = Grado::with('nivel')->find($value);
                        if ($grado && $grado->nivel->colegio_id != $colegioId) {
                            $fail('El grado seleccionado no pertenece al colegio indicado.');
                        }
                    }
                }
            ],
            'seccion' => 'required|string|max:10',
            'estado' => 'required|string|max:50',
        ]);

        $inscripcion = Inscripcion::create($validated);

        // --- Módulo Financiero V3: Generar Cargos Automáticamente ---
        $finanzas = new \App\Services\FinanzasService();
        $finanzas->generarCargosPorInscripcion($inscripcion);
        // ------------------------------------------------------------

        return response()->json($inscripcion, 201);
    }

    public function show($id)
    {
        $inscripcion = Inscripcion::with(['estudiante', 'ciclo', 'colegio', 'grado'])->find($id);

        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada'], 404);
        }

        return response()->json($inscripcion);
    }

    public function update(Request $request, $id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada'], 404);
        }

        $validated = $request->validate([
            'estudiante_id' => 'sometimes|required|exists:estudiantes,id',
            'ciclo_id' => 'sometimes|required|exists:ciclos,id',
            'colegio_id' => 'sometimes|required|exists:colegios,id',
            'grado_id' => 'sometimes|required|exists:grados,id',
            'seccion' => 'sometimes|required|string|max:10',
            'estado' => 'sometimes|required|string|max:50',
        ]);

        $inscripcion->update($validated);

        return response()->json($inscripcion);
    }

    public function destroy($id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada'], 404);
        }

        $inscripcion->delete();

        return response()->json(['message' => 'Inscripción eliminada']);
    }
}
