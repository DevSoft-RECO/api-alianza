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
        \App::setLocale('es');

        // 1. KPIs Principales
        $cicloActivo = Ciclo::where('activo', true)->first();
        $totalEstudiantes = Estudiante::count();
        $inscripcionesActivas = Inscripcion::where('ciclo_id', $cicloActivo->id ?? 0)->count();

        // 2. Ingresos: Mes Actual y Mes Pasado (Basado en Pago y fecha_pago)
        $lastMonth = $now->copy()->subMonth();
        
        $ingresosMesActual = Pago::whereMonth('fecha_pago', $now->month)
            ->whereYear('fecha_pago', $now->year)
            ->sum('total');

        $ingresosMesPasado = Pago::whereMonth('fecha_pago', $lastMonth->month)
            ->whereYear('fecha_pago', $lastMonth->year)
            ->sum('total');

        $nombreMesActual = $now->translatedFormat('F');
        $nombreMesPasado = $lastMonth->translatedFormat('F');

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

        // 4. Nueva Métrica: Recaudación por Colegio (Panorama General)
        $ingresosPorColegio = Pago::join('estudiantes', 'pagos.estudiante_id', '=', 'estudiantes.id')
            ->join('inscripciones', 'estudiantes.id', '=', 'inscripciones.estudiante_id')
            ->join('colegios', 'inscripciones.colegio_id', '=', 'colegios.id')
            ->select('colegios.nombre', DB::raw('sum(pagos.total) as total'))
            ->groupBy('colegios.nombre')
            ->get();

        // 5. Actividad Reciente: Últimas Inscripciones
        $ultimasInscripciones = Inscripcion::with(['estudiante:id,nombres,apellidos', 'grado:id,nombre', 'colegio:id,nombre'])
            ->latest()
            ->take(5)
            ->get();

        // 6. Actividad Reciente: Últimos Pagos
        $ultimosPagos = Pago::with(['estudiante:id,nombres,apellidos'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'kpis' => [
                'total_estudiantes' => $totalEstudiantes,
                'inscripciones_activas' => $inscripcionesActivas,
                'ingresos_mes_actual' => (float)$ingresosMesActual,
                'ingresos_mes_pasado' => (float)$ingresosMesPasado,
                'nombre_mes_actual' => ucfirst($nombreMesActual),
                'nombre_mes_pasado' => ucfirst($nombreMesPasado),
                'monto_pendiente' => (float)$montoPendiente,
                'ciclo_anio' => $cicloActivo->anio ?? 'N/A',
            ],
            'stats' => [
                'por_colegio' => $inscripcionesPorColegio,
                'por_metodo' => $ingresosPorMetodo,
                'recaudacion_por_colegio' => $ingresosPorColegio,
            ],
            'recents' => [
                'inscripciones' => $ultimasInscripciones,
                'pagos' => $ultimosPagos
            ]
        ]);
    }
}
