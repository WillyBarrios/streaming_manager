<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;

    // ESTA ES LA LÍNEA MÁGICA
    protected $guarded = []; 

    // Esta relación ya la tenías del paso anterior
    public function cuentas()
    {
        return $this->hasMany(Cuenta::class);
    }
}