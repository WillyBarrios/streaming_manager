<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_xx_xx_create_suscripciones_table.php

public function up(): void
{
    Schema::create('suscripciones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cliente_id')->constrained('clientes');
        $table->foreignId('perfil_id')->constrained('perfiles');
        
        $table->decimal('precio_pactado', 10, 2); // Precio real que paga el cliente por este perfil
        $table->date('fecha_inicio');
        $table->date('fecha_proximo_vencimiento');
        $table->enum('estado', ['Activo', 'Por Vencer', 'Vencido', 'Cancelado'])->default('Activo');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
