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
    protected static function booted(): void
    {
        static::created(function (Pago $pago) {
            $suscripcion = $pago->suscripcion;

            if ($suscripcion) {
                // 1. Determinar desde qué fecha sumar (Si ya venció, usar HOY. Si no, usar la fecha que tenía)
                $fechaBase = $suscripcion->fecha_proximo_vencimiento < now()
                    ? now()
                    : $suscripcion->fecha_proximo_vencimiento;

                // 2. Sumar 1 mes (Asumimos 1 mes por defecto al crear pago manual)
                $nuevaFecha = \Carbon\Carbon::parse($fechaBase)->addMonth();

                // 3. Actualizar la suscripción
                $suscripcion->update([
                    'fecha_proximo_vencimiento' => $nuevaFecha,
                    'estado' => 'Activo', // La reactivamos por si estaba vencida
                ]);
            }
        });
    }
}
