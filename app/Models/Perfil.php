<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Cuenta; // <--- Importante: Importar el modelo Cuenta

class Perfil extends Model
{
    use HasFactory;

    protected $table = 'perfiles';
    
    protected $guarded = [];

    // ESTA ES LA FUNCIÓN QUE TE FALTA Y CAUSA EL ERROR
    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class);
    }
    // ... (debajo de tus otras funciones)

    // Esto crea un atributo falso llamado "nombre_completo"
    public function getNombreCompletoAttribute()
    {
        // Ejemplo resultado: "Netflix (cuenta@gmail.com) - Perfil 1"
        return $this->cuenta->servicio->nombre . ' (' . $this->cuenta->correo_acceso . ') - ' . $this->nombre_perfil;
    }
}