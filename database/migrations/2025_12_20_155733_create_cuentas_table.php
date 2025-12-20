<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_xx_xx_create_cuentas_table.php

public function up(): void
{
    Schema::create('cuentas', function (Blueprint $table) {
        $table->id();
        // Relación con servicios
        $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
        
        $table->string('correo_acceso');
        $table->string('contrasena'); // Recuerda: aquí se guarda en texto plano o encriptado reversible
        $table->date('fecha_corte_proveedor'); // Cuándo pagas tú la cuenta completa
        $table->enum('estado', ['Activa', 'Suspendida', 'Caida'])->default('Activa');
        $table->text('nota_interna')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
