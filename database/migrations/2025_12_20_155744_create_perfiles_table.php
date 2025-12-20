<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_xx_xx_create_perfiles_table.php

public function up(): void
{
    Schema::create('perfiles', function (Blueprint $table) {
        $table->id();
        // Si borras la cuenta madre, se borran los perfiles automáticamente (cascade)
        $table->foreignId('cuenta_id')->constrained('cuentas')->onDelete('cascade');
        
        $table->string('nombre_perfil'); // Ej: Perfil 1, Kids
        $table->string('pin', 10)->nullable();
        $table->enum('estado', ['Disponible', 'Ocupado', 'Mantenimiento'])->default('Disponible');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
