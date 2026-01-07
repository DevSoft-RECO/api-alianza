<?php

namespace App\Observers;

use App\Models\Inscripcion;
use App\Models\GradoConcepto;
use App\Models\Cargo;
use Carbon\Carbon;

class InscripcionObserver
{
    public function created(Inscripcion $inscripcion)
    {
        // 1. Buscar configuración de cobros para este grado
        // IMPORTANT: Se filtra por una lógica implícita de colegio si es necesario,
        // pero aquí solo usamos el grado_id directo como pide el requerimiento.
        // Si hay que diferenciar por colegio, el grado debería ser único por colegio o filtrar por colegio_id usando whereHas

        $asignaciones = GradoConcepto::where('grado_id', $inscripcion->grado_id)
            ->with(['conceptoColegio.concepto', 'conceptoColegio'])
            ->get();

        foreach ($asignaciones as $asignacion) {
            $config = $asignacion->conceptoColegio;
            $concepto = $config->concepto;

            // Determinar si es mensualidad (varios cargos) o pago único (un cargo)
            if ($concepto->tipo === 'mensualidad' && $config->mes_inicio && $config->mes_fin) {
                // Generar ciclo de meses (Ej: Enero a Octubre)
                for ($mes = $config->mes_inicio; $mes <= $config->mes_fin; $mes++) {
                    $this->crearCargo($inscripcion, $concepto, $config, $mes);
                }
            } else {
                // Pago único (Inscripción, Libro, etc)
                $this->crearCargo($inscripcion, $concepto, $config, null);
            }
        }
    }

    private function crearCargo($inscripcion, $concepto, $config, $mes)
    {
        // Calcular fecha límite
        $fechaLimite = null;
        $anio = $inscripcion->ciclo->anio ?? date('Y'); // Asumimos relación con ciclo->anio

        if ($concepto->tiene_fecha_limite) {
            if ($mes) {
                // Para mensualidad: día 10 del mes (ejemplo, configurable si hubiera campo dia_limite)
                // Usaremos día 10 como default o el ultimo dia del mes si no se especifica
                // El requerimiento dice "Colegiatura -> mora Q15 después del día 10"
                $fechaLimite = Carbon::create($anio, $mes, 10);
            } else {
                // Para pago único, asumimos fin de mes actual o regla especifica
                $fechaLimite = Carbon::today()->endOfMonth();
            }
        }

        // Etiqueta de mes
        $nombreMes = $mes ? $this->nombreMes($mes) : '';
        $descripcion = trim($concepto->nombre . ' ' . $nombreMes);

        Cargo::create([
            'inscripcion_id' => $inscripcion->id,
            'concepto_id' => $concepto->id,
            'nombre_concepto' => $descripcion, // Foto histórica
            'mes' => $mes,
            'anio' => $anio,
            'monto_base' => $config->precio, // Precio congelado
            'mora_monto' => $concepto->tiene_mora ? $concepto->mora_monto : null, // Copia de regla
            'fecha_limite_pago' => $fechaLimite,
            'estado' => 'pendiente'
        ]);
    }

    private function nombreMes($num) {
        $meses = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
                  7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
        return $meses[$num] ?? '';
    }
}
