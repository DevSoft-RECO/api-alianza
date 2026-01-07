<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concepto extends Model {
    protected $fillable = ['nombre', 'descripcion', 'tipo', 'tiene_mora', 'mora_monto', 'dias_gracia'];
}
