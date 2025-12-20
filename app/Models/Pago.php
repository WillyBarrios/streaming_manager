<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $guarded = [];

    // ESTA ES LA PIEZA QUE FALTA
    public function suscripcion()
    {
        return $this->belongsTo(Suscripcion::class);
    }
}