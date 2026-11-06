<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Concepto;
use App\Models\ConceptoColegio;
use App\Models\GradoConcepto;
use Illuminate\Http\Request;

class FinanzasController extends Controller
{
    // --- 1. Conceptos (Abstractos) ---
    public function indexConceptos()
    {
        return response()->json(Concepto::withCount('precios')->get());
    }

    public function storeConcepto(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:mensual,unico',
            'tiene_mora' => 'boolean',
            'mora_monto' => 'nullable|numeric',
            'dias_gracia' => 'nullable|integer'
        ]);

        $concepto = Concepto::create($validated);
        return response()->json($concepto, 201);
    }

    public function updateConcepto(Request $request, $id)
    {
        $concepto = Concepto::findOrFail($id);
        $validated = $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:mensual,unico',
            'tiene_mora' => 'boolean',
            'mora_monto' => 'nullable|numeric',
            'dias_gracia' => 'nullable|integer'
        ]);

        $concepto->update($validated);
        return response()->json($concepto);
    }

    public function destroyConcepto($id)
    {
        $concepto = Concepto::findOrFail($id);
        // Validar si existen asignaciones o cobros ligados?
        // En un sistema estricto no se debería borrar si ya se usó, seria soft delete.
        // Pero el requerimiento pide "eliminar". Asumimos validación de integridad referencial de BD saltará error si se usa.
        try {
            $concepto->delete();
            return response()->json(['message' => 'Concepto eliminado']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No se puede eliminar porque este item ya está siendo usado (tiene precios definidos o cobros generados).'], 400);
        }
    }

    // --- 2. Conceptos por Colegio (Precios) ---
    public function indexPrecios(Request $request)
    {
        $query = ConceptoColegio::with(['concepto', 'colegio', 'ciclo'])->withCount('asignaciones');
        if ($request->filled('colegio_id')) {
            $query->where('colegio_id', $request->input('colegio_id'));
        }
        if ($request->filled('ciclo_id')) {
            $query->where('ciclo_id', $request->input('ciclo_id'));
        }
        return response()->json($query->get());
    }

    public function storePrecio(Request $request)
    {
        $validated = $request->validate([
            'ciclo_id' => 'required|exists:ciclos,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'colegio_id' => 'required|exists:colegios,id',
            'precio' => 'required|numeric',
            'fecha_limite_absoluta' => 'nullable|date',
            'mes_inicio' => 'nullable|integer|min:1|max:12',
            'mes_fin' => 'nullable|integer|min:1|max:12',
            'activo' => 'boolean'
        ]);

        $precio = ConceptoColegio::create($validated);
        return response()->json($precio, 201);
    }

    public function updatePrecio(Request $request, $id)
    {
        $precio = ConceptoColegio::findOrFail($id);
        $validated = $request->validate([
            'precio' => 'required|numeric',
            'fecha_limite_absoluta' => 'nullable|date',
            'mes_inicio' => 'nullable|integer|min:1|max:12',
            'mes_fin' => 'nullable|integer|min:1|max:12',
            'activo' => 'boolean'
        ]);

        $precio->update($validated);
        return response()->json($precio);
    }

    public function destroyPrecio($id)
    {
        $precio = ConceptoColegio::findOrFail($id);
        if ($precio->asignaciones()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar porque este precio está asignado a uno o más grados.'], 400);
        }
        $precio->delete();
        return response()->json(['message' => 'Precio eliminado']);
    }

    // --- 3. Asignación a Grados (Plantilla) ---
    public function indexAsignaciones(Request $request)
    {
        $query = GradoConcepto::with(['conceptoColegio.concepto', 'conceptoColegio.colegio', 'conceptoColegio.ciclo']);
        if ($request->filled('grado_id')) {
            $query->where('grado_id', $request->input('grado_id'));
        }
        if ($request->filled('ciclo_id')) {
            $query->whereHas('conceptoColegio', function($q) use ($request) {
                $q->where('ciclo_id', $request->input('ciclo_id'));
            });
        }
        $asignaciones = $query->get()->map(function($a) {
            // Contar cuántos cargos pendientes existen
            $a->pending_charges_count = \App\Models\Cargo::where('concepto_id', $a->conceptoColegio->concepto_id)
                ->where('estado', 'pendiente')
                ->whereHas('inscripcion', function($q) use ($a) {
                    $q->where('grado_id', $a->grado_id);
                })
                ->count();

            // Contar cuántos cargos PAGADOS existen (historial)
            $a->paid_charges_count = \App\Models\Cargo::where('concepto_id', $a->conceptoColegio->concepto_id)
                ->where('estado', 'pagado')
                ->whereHas('inscripcion', function($q) use ($a) {
                    $q->where('grado_id', $a->grado_id);
                })
                ->count();
            return $a;
        });

        // Si se pide un grado específico, buscar si hay cobros que NO tengan asignación
        $huerfanos = [];
        if ($request->filled('grado_id')) {
            $gradoId = $request->input('grado_id');
            $conceptosAsignadosIds = $asignaciones->pluck('conceptoColegio.concepto_id')->toArray();

            $huerfanos = \App\Models\Cargo::where('estado', 'pendiente')
                ->whereNotIn('concepto_id', $conceptosAsignadosIds)
                ->whereHas('inscripcion', function($q) use ($gradoId) {
                    $q->where('grado_id', $gradoId);
                })
                ->with('concepto')
                ->select('concepto_id')
                ->groupBy('concepto_id')
                ->get()
                ->map(function($c) {
                    return [
                        'id' => $c->concepto_id,
                        'nombre' => $c->concepto->nombre,
                        'tipo' => $c->concepto->tipo
                    ];
                });
        }

        return response()->json([
            'asignaciones' => $asignaciones,
            'huerfanos' => $huerfanos
        ]);
    }

    public function storeAsignacion(Request $request)
    {
        $validated = $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'concepto_colegio_id' => 'required|exists:concepto_colegio,id',
            'obligatorio' => 'boolean'
        ]);

        $asignacion = GradoConcepto::create($validated);

        // --- LÓGICA RETROACTIVA ---
        // Al asignar un nuevo cobro al grado, debemos cargárselo a los alumnos YA inscritos de ESE ciclo
        $config = \App\Models\ConceptoColegio::with('concepto')->find($validated['concepto_colegio_id']);

        $inscripciones = \App\Models\Inscripcion::where('grado_id', $validated['grado_id'])
            ->with('ciclo')
            ->where('ciclo_id', $config->ciclo_id)
            ->get();

        $concepto = $config->concepto;
        $servicio = new \App\Services\FinanzasService();

        foreach ($inscripciones as $inscripcion) {
            if ($concepto->tipo === 'mensual' && $config->mes_inicio && $config->mes_fin) {
                for ($mes = $config->mes_inicio; $mes <= $config->mes_fin; $mes++) {
                    $servicio->generarCargo($inscripcion, $concepto, $config, $mes);
                }
            } else {
                $servicio->generarCargo($inscripcion, $concepto, $config, null);
            }
        }

        return response()->json(['message' => 'Asignación creada y cargos generados', 'data' => $asignacion], 201);
    }

    public function destroyAsignacion($id)
    {
        $asignacion = GradoConcepto::findOrFail($id);
        $asignacion->delete();
        return response()->json(['message' => 'Asignación eliminada correctamente']);
    }

    public function reaplicarAsignacion($id)
    {
        $asignacion = GradoConcepto::findOrFail($id);
        $config = \App\Models\ConceptoColegio::with('concepto')->findOrFail($asignacion->concepto_colegio_id);

        $inscripciones = \App\Models\Inscripcion::where('grado_id', $asignacion->grado_id)
            ->where('ciclo_id', $config->ciclo_id)
            ->get();

        $concepto = $config->concepto;
        $servicio = new \App\Services\FinanzasService();
        $creados = 0;

        foreach ($inscripciones as $inscripcion) {
            if ($concepto->tipo === 'mensual' && $config->mes_inicio && $config->mes_fin) {
                for ($mes = $config->mes_inicio; $mes <= $config->mes_fin; $mes++) {
                    $cargo = $servicio->generarCargo($inscripcion, $concepto, $config, $mes);
                    if ($cargo) {
                        $creados++;
                    }
                }
            } else {
                $cargo = $servicio->generarCargo($inscripcion, $concepto, $config, null);
                if ($cargo) {
                    $creados++;
                }
            }
        }

        return response()->json([
            'message' => "Se han generado {$creados} nuevos cargos a los alumnos inscritos.",
            'cargos_creados' => $creados
        ]);
    }

    public function ajusteMasivo(Request $request)
    {
        $validated = $request->validate([
            'colegio_id' => 'required|exists:colegios,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'grado_id' => 'nullable|exists:grados,id', // Opcional para granularidad
            'mes' => 'nullable|integer|min:1|max:12',
            'anio' => 'required|integer',
            'nuevo_precio' => 'required|numeric|min:0'
        ]);

        $query = \App\Models\Cargo::where('concepto_id', $validated['concepto_id'])
            ->where('anio', $validated['anio'])
            ->where('estado', 'pendiente')
            ->whereHas('inscripcion', function($q) use ($validated) {
                $q->where('colegio_id', $validated['colegio_id']);
                if ($validated['grado_id']) {
                    $q->where('grado_id', $validated['grado_id']);
                }
            });

        if ($request->filled('mes')) {
            $query->where('mes', $validated['mes']);
        } else {
            $query->whereNull('mes');
        }

        $updatedCount = $query->update(['monto_base' => $validated['nuevo_precio']]);

        return response()->json([
            'message' => "Se han actualizado {$updatedCount} cargos pendientes.",
            'updated_count' => $updatedCount
        ]);
    }

    public function eliminarCargosMasivo(Request $request)
    {
        $validated = $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'mes' => 'nullable|integer|min:1|max:12',
            'anio' => 'required|integer'
        ]);

        $query = \App\Models\Cargo::where('concepto_id', $validated['concepto_id'])
            ->where('anio', $validated['anio'])
            ->where('estado', 'pendiente')
            ->whereHas('inscripcion', function($q) use ($validated) {
                $q->where('grado_id', $validated['grado_id']);
            });

        if ($request->filled('mes')) {
            $query->where('mes', $validated['mes']);
        } else {
            $query->whereNull('mes');
        }

        $deletedCount = $query->delete();

        return response()->json([
            'message' => "Se han eliminado {$deletedCount} cargos pendientes correctamente.",
            'deleted_count' => $deletedCount
        ]);
    }

    public function clonarCiclo(Request $request)
    {
        $validated = $request->validate([
            'desde_ciclo_id' => 'required|exists:ciclos,id',
            'hacia_ciclo_id' => 'required|exists:ciclos,id|different:desde_ciclo_id'
        ]);

        $desdeId = $validated['desde_ciclo_id'];
        $haciaId = $validated['hacia_ciclo_id'];

        return \Illuminate\Support\Facades\DB::transaction(function () use ($desdeId, $haciaId) {
            $conteoExistentes = ConceptoColegio::where('ciclo_id', $haciaId)->count();
            if ($conteoExistentes > 0) {
                return response()->json([
                    'message' => 'El ciclo destino ya cuenta con tarifas configuradas. Para evitar duplicados, elimínelas primero si desea volver a clonar.'
                ], 422);
            }

            $preciosOrigen = ConceptoColegio::where('ciclo_id', $desdeId)->get();

            if ($preciosOrigen->isEmpty()) {
                return response()->json([
                    'message' => 'El ciclo origen no tiene tarifas configuradas para copiar.'
                ], 422);
            }

            $mapaIdsPrecios = [];
            foreach ($preciosOrigen as $precio) {
                $nuevoPrecio = ConceptoColegio::create([
                    'ciclo_id' => $haciaId,
                    'concepto_id' => $precio->concepto_id,
                    'colegio_id' => $precio->colegio_id,
                    'precio' => $precio->precio,
                    'fecha_limite_absoluta' => $precio->fecha_limite_absoluta,
                    'mes_inicio' => $precio->mes_inicio,
                    'mes_fin' => $precio->mes_fin,
                    'activo' => $precio->activo
                ]);

                $mapaIdsPrecios[$precio->id] = $nuevoPrecio->id;
            }

            $asignacionesOrigen = GradoConcepto::whereIn('concepto_colegio_id', array_keys($mapaIdsPrecios))->get();
            $conteoAsignaciones = 0;

            foreach ($asignacionesOrigen as $asig) {
                if (isset($mapaIdsPrecios[$asig->concepto_colegio_id])) {
                    GradoConcepto::create([
                        'grado_id' => $asig->grado_id,
                        'concepto_colegio_id' => $mapaIdsPrecios[$asig->concepto_colegio_id],
                        'obligatorio' => $asig->obligatorio
                    ]);
                    $conteoAsignaciones++;
                }
            }

            return response()->json([
                'message' => "Configuración copiada exitosamente: {$preciosOrigen->count()} precios y {$conteoAsignaciones} asignaciones a grados.",
                'precios_clonados' => $preciosOrigen->count(),
                'asignaciones_clonadas' => $conteoAsignaciones
            ]);
        });
    }
}
