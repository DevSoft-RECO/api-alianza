<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nivel extends Model
{
    use HasFactory;

    protected $table = 'niveles';
    protected $fillable = ['colegio_id', 'nombre', 'descripcion', 'cantidad_colegiaturas'];

    public function colegio()
    {
        return $this->belongsTo(Colegio::class);
    }

    public function grados()
    {
        return $this->hasMany(Grado::class);
    }
}
