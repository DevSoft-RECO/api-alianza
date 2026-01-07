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
        return response()->json(Concepto::all());
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
        $query = ConceptoColegio::with(['concepto', 'colegio']);
        if ($request->filled('colegio_id')) {
            $query->where('colegio_id', $request->input('colegio_id'));
        }
        return response()->json($query->get());
    }

    public function storePrecio(Request $request)
    {
        $validated = $request->validate([
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

    // --- 3. Asignación a Grados (Plantilla) ---
    public function indexAsignaciones(Request $request)
    {
        $query = GradoConcepto::with(['conceptoColegio.concepto', 'conceptoColegio.colegio']);
        if ($request->filled('grado_id')) {
            $query->where('grado_id', $request->input('grado_id'));
        }
        return response()->json($query->get());
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
        // Al asignar un nuevo cobro al grado, debemos cargárselo a los alumnos YA inscritos.
        // Buscamos inscripciones activas (estado? ciclo activo?)
        // Asumiremos ciclo activo, pero por seguridad iteramos las inscripciones de ese grado.

        $inscripciones = \App\Models\Inscripcion::where('grado_id', $validated['grado_id'])
            ->with('ciclo') // Necesario para saber el año
            ->whereHas('ciclo', function($q) { $q->where('activo', true); }) // Solo ciclo actual
            ->get();

        $config = \App\Models\ConceptoColegio::with('concepto')->find($validated['concepto_colegio_id']);
        $concepto = $config->concepto;
        $servicio = new \App\Services\FinanzasService();

        foreach ($inscripciones as $inscripcion) {
            // Reutilizamos la lógica del servicio
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
        // Validar si ya hay cobros pagados ligados a esto?
        // Por simplificación V3, permitimos borrar la configuración.
        // Los cargos generados (históricos) NO se borran en cascada automáticamente si no se desea,
        // pero la FK dice 'cascade' en grado_concepto -> cargos? No, grado_concepto es una plantilla.
        // Cargos tiene FK a inscripciones y conceptos, NO a grado_concepto directamente.
        // Entonces es seguro borrar la "plantilla" sin afectar lo cobrado.
        $asignacion->delete();
        return response()->json(['message' => 'Asignación eliminada correctamente']);
    }
}
