<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Pago;
use App\Models\Cargo;
use App\Models\Ciclo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // 1. KPIs Principales
        $cicloActivo = Ciclo::where('activo', true)->first();
        $totalEstudiantes = Estudiante::count();
        $inscripcionesActivas = Inscripcion::where('ciclo_id', $cicloActivo->id ?? 0)->count();

        $ingresosMes = Pago::whereMonth('fecha_pago', $now->month)
            ->whereYear('fecha_pago', $now->year)
            ->sum('total');

        $montoPendiente = Cargo::where('estado', 'pendiente')->sum('monto_base');

        // 2. Gráfico: Inscripciones por Colegio
        $inscripcionesPorColegio = Inscripcion::join('colegios', 'inscripciones.colegio_id', '=', 'colegios.id')
            ->select('colegios.nombre', DB::raw('count(*) as total'))
            ->groupBy('colegios.nombre')
            ->get();

        // 3. Gráfico: Ingresos por Forma de Pago (Este mes)
        $ingresosPorMetodo = Pago::select('forma_pago', DB::raw('sum(total) as total'))
            ->whereMonth('fecha_pago', $now->month)
            ->whereYear('fecha_pago', $now->year)
            ->groupBy('forma_pago')
            ->get();

        // 4. Actividad Reciente: Últimas Inscripciones
        $ultimasInscripciones = Inscripcion::with(['estudiante:id,nombres,apellidos', 'grado:id,nombre', 'colegio:id,nombre'])
            ->latest()
            ->take(5)
            ->get();

        // 5. Actividad Reciente: Últimos Pagos
        $ultimosPagos = Pago::with(['estudiante:id,nombres,apellidos'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'kpis' => [
                'total_estudiantes' => $totalEstudiantes,
                'inscripciones_activas' => $inscripcionesActivas,
                'ingresos_mes' => (float)$ingresosMes,
                'monto_pendiente' => (float)$montoPendiente,
                'ciclo_anio' => $cicloActivo->anio ?? 'N/A',
            ],
            'stats' => [
                'por_colegio' => $inscripcionesPorColegio,
                'por_metodo' => $ingresosPorMetodo,
            ],
            'recents' => [
                'inscripciones' => $ultimasInscripciones,
                'pagos' => $ultimosPagos
            ]
        ]);
    }
}
