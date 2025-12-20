<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Suscripcion extends Model
{
    use HasFactory;

    // 1. SOLUCIÓN DEL ERROR
    protected $table = 'suscripciones';
    
    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_proximo_vencimiento' => 'date',
    ];

    // 2. Relación: Una suscripción pertenece a un Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // 3. Relación: Una suscripción ocupa un Perfil
    public function perfil()
    {
        return $this->belongsTo(Perfil::class);
    }
    // ... (el resto de tu código sigue igual)

    protected static function booted(): void
    {
        // 1. AL CREAR UNA VENTA: Poner Perfil en "Ocupado"
        static::created(function (Suscripcion $suscripcion) {
            if ($suscripcion->perfil) {
                $suscripcion->perfil->update(['estado' => 'Ocupado']);
            }
        });

        // 2. AL ACTUALIZAR (Ej: Si cancelas la suscripción):
        static::updated(function (Suscripcion $suscripcion) {
            if ($suscripcion->perfil) {
                // Si cambiamos el estado a Cancelado o Vencido -> Liberar Perfil
                if (in_array($suscripcion->estado, ['Cancelado', 'Vencido'])) {
                    $suscripcion->perfil->update(['estado' => 'Disponible']);
                }
                // Si la reactivamos (volvemos a poner Activo) -> Ocupar Perfil
                elseif ($suscripcion->estado === 'Activo') {
                    $suscripcion->perfil->update(['estado' => 'Ocupado']);
                }
            }
        });

        // 3. AL ELIMINAR LA SUSCRIPCIÓN (Borrar): Liberar Perfil
        static::deleted(function (Suscripcion $suscripcion) {
            if ($suscripcion->perfil) {
                $suscripcion->perfil->update(['estado' => 'Disponible']);
            }
        });
    }
}
