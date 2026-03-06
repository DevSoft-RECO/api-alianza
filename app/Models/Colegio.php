<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Colegio extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'director', 'direccion', 'label', 'descripcion_web', 'theme'];

    public function niveles()
    {
        return $this->hasMany(Nivel::class);
    }

    public function catalogoCarreras()
    {
        return $this->hasMany(CatalogoCarrera::class);
    }
}
