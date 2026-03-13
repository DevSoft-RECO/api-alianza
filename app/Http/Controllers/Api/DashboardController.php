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
    public function index(Request $request)
    {
        $now = Carbon::now();
        \App::setLocale('es');

        // Filtros (Opcionales)
        $selectedMonth = $request->query('month');
        $selectedYear = $request->query('year', $now->year);

        // Validar tipo
        if ($selectedMonth && is_numeric($selectedMonth)) {
            $selectedMonth = (int)$selectedMonth;
        } else {
            $selectedMonth = null;
        }

        // 1. KPIs Principales
        $cicloActivo = Ciclo::where('activo', true)->first();
        $totalEstudiantes = Estudiante::count();
        $inscripcionesActivas = Inscripcion::where('ciclo_id', $cicloActivo->id ?? 0)->count();

        // 2. Ingresos: Mes Seleccionado (o Actual por defecto)
        $monthForKpi = $selectedMonth ?: $now->month;
        $yearForKpi = $selectedYear ?: $now->year;
        
        $ingresosMesActual = Pago::whereMonth('fecha_pago', $monthForKpi)
            ->whereYear('fecha_pago', $yearForKpi)
            ->sum('total');

        $nombreMesActual = Carbon::now()->setMonth($monthForKpi)->translatedFormat('F');

        $montoPendiente = Cargo::join('inscripciones', 'cargos.inscripcion_id', '=', 'inscripciones.id')
            ->where('inscripciones.ciclo_id', $cicloActivo->id ?? 0)
            ->where('cargos.estado', 'pendiente')
            ->sum('cargos.monto_base');

        // 3. Gráfico: Inscripciones por Colegio (Solo ciclo activo)
        $inscripcionesPorColegio = Inscripcion::join('colegios', 'inscripciones.colegio_id', '=', 'colegios.id')
            ->where('inscripciones.ciclo_id', $cicloActivo->id ?? 0)
            ->select('colegios.nombre', DB::raw('count(*) as total'))
            ->groupBy('colegios.nombre')
            ->get();

        // 4. Métrica: Recaudación por Colegio (Panorama General o Filtrado)
        $queryRecaudacion = Pago::join('estudiantes', 'pagos.estudiante_id', '=', 'estudiantes.id')
            ->join('inscripciones', 'estudiantes.id', '=', 'inscripciones.estudiante_id')
            ->join('colegios', 'inscripciones.colegio_id', '=', 'colegios.id')
            ->where('inscripciones.ciclo_id', $cicloActivo->id ?? 0) // Guardián de ciclo
            ->select('colegios.nombre', DB::raw('sum(pagos.total) as total'))
            ->groupBy('colegios.nombre');

        if ($selectedMonth) {
            $queryRecaudacion->whereMonth('pagos.fecha_pago', $selectedMonth)
                            ->whereYear('pagos.fecha_pago', $selectedYear);
        }

        $recaudacionPorColegio = $queryRecaudacion->get();

        // 5. Actividad Reciente: Últimos Pagos
        $ultimosPagos = Pago::with(['estudiante:id,nombres,apellidos'])
            ->latest()
            ->take(8)
            ->get();

        // 6. Métricas Globales (Basadas en Ciclo Activo)
        $totalRecaudadoGlobal = Pago::join('estudiantes', 'pagos.estudiante_id', '=', 'estudiantes.id')
            ->join('inscripciones', 'estudiantes.id', '=', 'inscripciones.estudiante_id')
            ->where('inscripciones.ciclo_id', $cicloActivo->id ?? 0)
            ->sum('pagos.total');

        $proyeccionIngresos = Cargo::join('inscripciones', 'cargos.inscripcion_id', '=', 'inscripciones.id')
            ->where('inscripciones.ciclo_id', $cicloActivo->id ?? 0)
            ->sum('cargos.monto_base');

        // Listado de meses para el filtro
        $meses = [];
        for ($i = 1; $i <= 12; $i++) {
            $meses[] = [
                'id' => $i,
                'nombre' => ucfirst(Carbon::now()->setDay(1)->setMonth($i)->translatedFormat('F'))
            ];
        }

        return response()->json([
            'kpis' => [
                'ingresos_mes_actual' => (float)$ingresosMesActual,
                'total_recaudado' => (float)$totalRecaudadoGlobal,
                'proyeccion_ingresos' => (float)$proyeccionIngresos,
                'nombre_mes_actual' => ucfirst($nombreMesActual),
                'monto_pendiente' => (float)$montoPendiente,
                'ciclo_anio' => $cicloActivo->anio ?? 'N/A',
            ],
            'stats' => [
                'por_colegio' => $inscripcionesPorColegio,
                'recaudacion_por_colegio' => $recaudacionPorColegio,
                'meses' => $meses,
                'filtro_actual' => [
                    'month' => $selectedMonth,
                    'year' => $selectedYear,
                    'label' => $selectedMonth ? ucfirst(Carbon::now()->setDay(1)->setMonth($selectedMonth)->translatedFormat('F')) : 'Total General'
                ]
            ],
            'recents' => [
                'pagos' => $ultimosPagos
            ]
        ]);
    }
}
