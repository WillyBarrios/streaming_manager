<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_xx_xx_create_servicios_table.php

public function up(): void
{
    Schema::create('servicios', function (Blueprint $table) {
        $table->id();
        $table->string('nombre'); // Ej: Netflix
        $table->decimal('precio_costo', 10, 2); // Cuánto pagas tú
        $table->decimal('precio_venta_sugerido', 10, 2); // Precio al público
        $table->integer('max_perfiles')->default(1); // Ej: 5 perfiles
        $table->string('logo_url')->nullable(); 
        $table->timestamps(); // Crea created_at y updated_at
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
