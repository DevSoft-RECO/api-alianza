<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Colegio extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'director', 'direccion'];

    public function niveles()
    {
        return $this->hasMany(Nivel::class);
    }
}
