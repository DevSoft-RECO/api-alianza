<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoCarrera extends Model
{
    protected $fillable = ['colegio_id', 'nombre', 'jornada', 'detalles', 'badge', 'icon'];

    protected $casts = [
        'detalles' => 'array',
    ];

    public function colegio()
    {
        return $this->belongsTo(Colegio::class);
    }
}
