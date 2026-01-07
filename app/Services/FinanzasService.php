<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Concepto;
use App\Models\ConceptoColegio;
use App\Models\Cargo;
use Carbon\Carbon;

class FinanzasService
{
    /**
     * Genera los cargos para una inscripción basado en la configuración del colegio.
     * Soporta mensualidades (ciclo) y pagos únicos.
     */
    public function generarCargo(Inscripcion $inscripcion, Concepto $concepto, ConceptoColegio $config, $mes = null)
    {
        // Calcular fecha límite BASE (Sin incluir días de gracia aún)
        $fechaLimite = null;
        $anio = $inscripcion->ciclo->anio ?? date('Y');

        if ($concepto->tipo === 'unico') {
             if ($config->fecha_limite_absoluta) {
                 $fechaLimite = Carbon::parse($config->fecha_limite_absoluta);
             } else {
                 $fechaLimite = Carbon::today()->endOfMonth(); // Default si no se configuro
             }
        } else {
             // Mensualidades: Fin de mes del mes cobrado
             if ($mes) {
                 $fechaLimite = Carbon::create($anio, $mes, 1)->endOfMonth();
             }
        }

        // Etiqueta de mes
        $nombreMes = $mes ? $this->nombreMes($mes) : '';
        $descripcion = trim($concepto->nombre . ' ' . $nombreMes);

        // Evitar duplicados (Clave: inscripción + concepto + mes + año)
        $existe = Cargo::where('inscripcion_id', $inscripcion->id)
            ->where('concepto_id', $concepto->id)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->exists();

        if ($existe) {
            return null; // Ya existe, no crear
        }

        return Cargo::create([
            'inscripcion_id' => $inscripcion->id,
            'concepto_id' => $concepto->id,
            'nombre_concepto' => $descripcion,
            'mes' => $mes,
            'anio' => $anio,
            'monto_base' => $config->precio,
            'mora_monto' => $concepto->tiene_mora ? $concepto->mora_monto : null,
            'fecha_limite_pago' => $fechaLimite,
            'estado' => 'pendiente'
        ]);
    }

    private function nombreMes($num) {
        $meses = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
                  7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
        return $meses[$num] ?? '';
    }

    /**
     * Busca la configuración del grado y genera todos los cargos aplicables.
     */
    public function generarCargosPorInscripcion(Inscripcion $inscripcion)
    {
        // 1. Buscar las asignaciones (templates) del grado
        $asignaciones = \App\Models\GradoConcepto::with(['conceptoColegio.concepto'])
            ->where('grado_id', $inscripcion->grado_id)
            ->get();

        foreach ($asignaciones as $asignacion) {
            $config = $asignacion->conceptoColegio;
            $concepto = $config->concepto;

            // 2. Verificar rango de meses
            if ($concepto->tipo === 'mensual' && $config->mes_inicio && $config->mes_fin) {
                // Generar un cargo por cada mes configurado
                for ($mes = $config->mes_inicio; $mes <= $config->mes_fin; $mes++) {
                    $this->generarCargo($inscripcion, $concepto, $config, $mes);
                }
            } else {
                // Cargo único (Inscripción, libro, etc)
                $this->generarCargo($inscripcion, $concepto, $config, null);
            }
        }
    }
}
