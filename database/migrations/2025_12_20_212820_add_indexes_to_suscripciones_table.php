<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('suscripciones', function (Blueprint $table) {
        // Esto hace que las búsquedas por estado y fecha sean inmediatas
        $table->index(['estado', 'fecha_proximo_vencimiento']);
        $table->index('cliente_id');
        $table->index('perfil_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suscripciones', function (Blueprint $table) {
            //
        });
    }
};
