<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cargo extends Model {
    protected $fillable = ['inscripcion_id', 'concepto_id', 'nombre_concepto', 'mes', 'anio', 'monto_base', 'mora_monto', 'fecha_limite_pago', 'estado'];

    protected $casts = [
        'monto_base' => 'float',
        'mora_monto' => 'float',
    ];

    protected $appends = ['mora_actual', 'total_con_mora', 'total_pagar'];

    // Relación al concepto abstracto
    public function concepto() {
        return $this->belongsTo(Concepto::class);
    }

    // Relación a la inscripción
    public function inscripcion() {
        return $this->belongsTo(Inscripcion::class);
    }

    // Calcula la mora en tiempo real (NO se guarda en BD)
    public function getMoraActualAttribute() {
        if ($this->estado === 'pendiente' && $this->fecha_limite_pago && $this->mora_monto) {
            // Buscamos los días de gracia del concepto original
            $diasGracia = $this->concepto->dias_gracia ?? 0;

            // La fecha de mora real es: Fecha Limite + Días Gracia
            $inicioMora = Carbon::parse($this->fecha_limite_pago)->addDays($diasGracia);

            // Si hoy es MAYOR (gt) que esa fecha extendida, cobramos mora.
            if (Carbon::now()->gt($inicioMora)) {
                return (float) $this->mora_monto;
            }
        }
        return 0;
    }

    public function getTotalConMoraAttribute() {
        return (float) $this->monto_base + $this->mora_actual;
    }

    public function getTotalPagarAttribute() {
        return $this->total_con_mora;
    }
}
