<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuenta extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relación con Servicio
    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }

    // Relación con Perfiles (para que funcione el borrado en cascada)
    public function perfiles()
    {
        return $this->hasMany(Perfil::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('estado', 'Activa')
            ->with('servicio');
    }

    public function getNombreServicioAttribute(): string
    {
        return (string) $this->servicio?->nombre;
    }

    public function getCostoMensualAttribute(): string
    {
        return (string) ($this->servicio?->precio_costo ?? '0.00');
    }

    public function getFechaProximoPagoAttribute(): mixed
    {
        return $this->fecha_corte_proveedor;
    }

    protected function casts(): array
    {
        return [
            'fecha_corte_proveedor' => 'date',
        ];
    }

    // LÓGICA AUTOMÁTICA
    protected static function booted(): void
    {
        static::created(function (Cuenta $cuenta) {
            // Verifica si hay servicio asociado
            if ($cuenta->servicio) {
                $maxPerfiles = $cuenta->servicio->max_perfiles;

                for ($i = 1; $i <= $maxPerfiles; $i++) {
                    Perfil::create([
                        'cuenta_id' => $cuenta->id,
                        'nombre_perfil' => 'Perfil '.$i,
                        'estado' => 'Disponible',
                    ]);
                }
            }
        });
    }
}
