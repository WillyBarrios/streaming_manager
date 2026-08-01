<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->index(
                ['estado', 'fecha_corte_proveedor'],
                'cuentas_estado_fecha_corte_index',
            );
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->index('fecha_pago', 'pagos_fecha_pago_index');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->dropIndex('cuentas_estado_fecha_corte_index');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex('pagos_fecha_pago_index');
        });
    }
};
