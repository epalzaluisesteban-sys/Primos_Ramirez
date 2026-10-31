<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doble moneda (USD / Bs.) anclada a la tasa oficial del BCV.
 * Los precios y deudas se llevan en USD (moneda base); cada operación guarda la tasa
 * con la que se hizo, para que el monto en bolívares histórico no cambie con el tiempo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_cambio', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->decimal('tasa', 14, 4);           // Bs. por 1 USD
            $table->string('fuente', 20)->default('BCV'); // BCV (automática) | manual
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('tasa_cambio', 14, 4)->nullable()->after('total');
            $table->decimal('total_ves', 18, 2)->nullable()->after('tasa_cambio');
            $table->string('moneda_pago', 3)->default('USD')->after('tipo_pago'); // USD | VES
        });

        Schema::table('abonos', function (Blueprint $table) {
            $table->string('moneda', 3)->default('USD')->after('monto');
            $table->decimal('monto_moneda', 18, 2)->nullable()->after('moneda'); // monto en la moneda en que pagó
            $table->decimal('tasa_cambio', 14, 4)->nullable()->after('monto_moneda');
        });

        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->decimal('monto_apertura_ves', 18, 2)->default(0)->after('monto_apertura');
            $table->decimal('monto_ventas_sistema_ves', 18, 2)->default(0)->after('monto_ventas_sistema');
            $table->decimal('monto_efectivo_real_ves', 18, 2)->nullable()->after('monto_efectivo_real');
            $table->decimal('diferencia_ves', 18, 2)->nullable()->after('diferencia');
        });
    }

    public function down(): void
    {
        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->dropColumn(['monto_apertura_ves', 'monto_ventas_sistema_ves', 'monto_efectivo_real_ves', 'diferencia_ves']);
        });
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropColumn(['moneda', 'monto_moneda', 'tasa_cambio']);
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['tasa_cambio', 'total_ves', 'moneda_pago']);
        });
        Schema::dropIfExists('tasas_cambio');
    }
};
