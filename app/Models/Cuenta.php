<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Perfil; // <--- Importante importar esto

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
                        'nombre_perfil' => 'Perfil ' . $i,
                        'estado' => 'Disponible',
                    ]);
                }
            }
        });
    }
}