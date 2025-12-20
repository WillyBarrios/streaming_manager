<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_xx_xx_create_pagos_table.php

public function up(): void
{
    Schema::create('pagos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('suscripcion_id')->constrained('suscripciones');
        
        $table->decimal('monto', 10, 2);
        $table->dateTime('fecha_pago')->useCurrent();
        $table->enum('metodo_pago', ['Efectivo', 'Transferencia', 'Deposito'])->default('Transferencia');
        $table->string('comprobante_url')->nullable(); // Para subir foto del boucher
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
