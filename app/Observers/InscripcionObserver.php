<?php

namespace App\Observers;

use App\Models\Inscripcion;
use App\Services\FinanzasService;

class InscripcionObserver
{
    public function created(Inscripcion $inscripcion)
    {
        // Delegar la generación de cargos al servicio oficial de finanzas
        $finanzas = new FinanzasService();
        $finanzas->generarCargosPorInscripcion($inscripcion);
    }
}
