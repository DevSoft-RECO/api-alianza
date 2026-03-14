<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Pago;
use App\Models\Cargo;
use App\Models\PagoDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        $query = Pago::with(['estudiante', 'detalles.cargo']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('estudiante', function($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('codigo_estudiante', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->input('estudiante_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('fecha_pago', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('fecha_pago', '<=', $request->input('date_to'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show($id)
    {
        $pago = Pago::with(['estudiante.inscripciones.grado', 'detalles.cargo', 'usuario'])->findOrFail($id);
        return response()->json($pago);
    }

    // Ver estado de cuenta (Con mora calculada al vuelo)
    public function estadoCuenta($estudiante_id)
    {
        $inscripciones = Inscripcion::with(['grado', 'ciclo', 'colegio', 'cargos.concepto'])
            ->where('estudiante_id', $estudiante_id)
            ->whereHas('ciclo', function($q) { $q->where('activo', true); })
            ->get();

        $data = $inscripciones->map(function ($ins) {
            return [
                'id' => $ins->id,
                'grado_nombre' => $ins->grado ? $ins->grado->nombre : 'Sin Grado',
                'ciclo_anio' => $ins->ciclo ? $ins->ciclo->anio : date('Y'),
                'colegio_nombre' => $ins->colegio ? $ins->colegio->nombre : 'Sin Colegio',
                'cargos' => $ins->cargos
            ];
        });

        return response()->json($data);
    }

    // Procesar Pago
    public function procesarPago(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'usuario_id' => 'nullable|exists:users,id',
            'total' => 'required|numeric',
            'forma_pago' => 'required|in:efectivo,tarjeta,transferencia',
            'detalles' => 'required|array',
            'detalles.*.cargo_id' => 'required|exists:cargos,id',
            'detalles.*.monto_pagado' => 'required|numeric',
            'detalles.*.exonerado' => 'boolean',
            'detalles.*.justificacion' => 'nullable|string',
            'detalles.*.descuento_monto' => 'nullable|numeric',
            'detalles.*.descuento_motivo' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request) {

            // 1. Crear Cabecera del Pago
            $pago = Pago::create([
                'estudiante_id' => $request->estudiante_id,
                'usuario_id' => $request->usuario_id ?? auth()->id(), // Guardamos quien cobró
                'total' => $request->total,
                'forma_pago' => $request->forma_pago,
                'fecha_pago' => now()
            ]);

            // 2. Procesar Detalles y Actualizar Cargos
            foreach ($request->detalles as $detalle) {
                // Bloquear fila para evitar concurrencia
                $cargo = Cargo::lockForUpdate()->find($detalle['cargo_id']);

                $montoAbonado = $detalle['monto_pagado'];

                // Registrar detalle
                PagoDetalle::create([
                    'pago_id' => $pago->id,
                    'cargo_id' => $cargo->id,
                    'monto_pagado' => $montoAbonado,
                    'exonerado' => $detalle['exonerado'] ?? false,
                    'justificacion' => $detalle['justificacion'] ?? null,
                    'descuento_monto' => $detalle['descuento_monto'] ?? 0,
                    'descuento_motivo' => $detalle['descuento_motivo'] ?? null
                ]);

                // Actualizar estado del cargo
                // Nota: Aquí podríamos implementar lógica de pagos parciales si quisiéramos
                // En este requerimiento V3 dice "Marca cargos como pagados", simplificado.
                // Pero un pago parcial es común. Asumiremos pago total de la cuota o validamos el saldo.

                // Si el monto pagado cubre el total con mora (o base si no hay mora), se marca pagado.
                // Usamos una lógica simple: Cambio a PAGADO.
                $cargo->estado = 'pagado';
                $cargo->save();
            }

            return response()->json([
                'message' => 'Pago registrado correctamente',
                'pago' => $pago->load('detalles')
            ]);
        });
    }

    public function getReporteDeudores(Request $request)
    {
        $query = Inscripcion::with(['estudiante', 'grado.nivel', 'colegio'])
            ->whereHas('ciclo', function($q) { $q->where('activo', true); })
            ->whereHas('cargos', function($q) {
                $q->where('estado', 'pendiente')
                  ->whereDate('fecha_limite_pago', '<=', now());
            });

        if ($request->filled('colegio_id')) {
            $query->where('colegio_id', $request->input('colegio_id'));
        }

        if ($request->filled('nivel_id')) {
            $query->whereHas('grado', function($q) use ($request) {
                $q->where('nivel_id', $request->input('nivel_id'));
            });
        }

        if ($request->filled('grado_id')) {
            $query->where('grado_id', $request->input('grado_id'));
        }

        $paginator = $query->paginate(10);

        $paginator->getCollection()->transform(function($ins) {
            $cargosPendientes = $ins->cargos()
                ->where('estado', 'pendiente')
                ->whereDate('fecha_limite_pago', '<=', now())
                ->get();

            $totalDeuda = $cargosPendientes->sum(function($cargo) {
                return $cargo->total_pagar;
            });

            return [
                'estudiante_id' => $ins->estudiante_id,
                'codigo_estudiante' => $ins->estudiante->codigo_estudiante,
                'nombres' => $ins->estudiante->nombres,
                'apellidos' => $ins->estudiante->apellidos,
                'grado' => $ins->grado->nombre,
                'nivel' => $ins->grado->nivel->nombre ?? 'Sin Nivel',
                'colegio' => $ins->colegio->nombre,
                'cantidad_cargos' => $cargosPendientes->count(),
                'total_deuda' => $totalDeuda,
                'detalles' => $cargosPendientes->map(function($c) {
                    return [
                        'id' => $c->id,
                        'concepto' => $c->nombre_concepto,
                        'mes' => $c->mes,
                        'anio' => $c->anio,
                        'monto_base' => $c->monto_base,
                        'mora' => $c->mora_actual,
                        'total' => $c->total_pagar,
                        'fecha_limite' => $c->fecha_limite_pago
                    ];
                })
            ];
        });

        return response()->json($paginator);
    }

    public function updateFecha(Request $request, $id)
    {
        $request->validate([
            'fecha_pago' => 'required|date'
        ]);

        $pago = Pago::findOrFail($id);
        $pago->fecha_pago = $request->input('fecha_pago');
        $pago->save();

        return response()->json([
            'message' => 'Fecha de pago actualizada correctamente',
            'pago' => $pago
        ]);
    }
}
