<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proveedores: qué categorías distribuye cada uno, si da crédito y a cuántos días.
 * Compras de contado o a crédito (con fecha de vencimiento) y pagos a proveedores para saber cuánto se les debe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->boolean('da_credito')->default(false)->after('rif');
            $table->unsignedSmallInteger('dias_credito')->nullable()->after('da_credito');
        });

        // Lo que surte cada proveedor. No es historial: si se borra la categoría o el proveedor, se va con ellos.
        Schema::create('categoria_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->unique(['categoria_id', 'proveedor_id']);
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->string('tipo_pago', 10)->default('contado')->after('total'); // contado | credito
            $table->date('vence_el')->nullable()->after('tipo_pago');
        });

        Schema::create('pagos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // Con caja: el dinero salió del cajón de ese día y se resta en su cuadre
            $table->foreignId('cierre_caja_id')->nullable()->constrained('cierre_cajas')->restrictOnDelete();
            $table->decimal('monto', 10, 2);                 // en USD: lo que baja la deuda
            $table->string('moneda', 3)->default('USD');     // USD | VES
            $table->decimal('monto_moneda', 18, 2);          // lo que se pagó en esa moneda
            $table->decimal('tasa_cambio', 14, 4)->nullable();
            $table->string('notas', 500)->nullable();
            $table->timestamp('fecha')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_proveedor');
        Schema::table('compras', fn (Blueprint $table) => $table->dropColumn(['tipo_pago', 'vence_el']));
        Schema::dropIfExists('categoria_proveedor');
        Schema::table('proveedores', fn (Blueprint $table) => $table->dropColumn(['da_credito', 'dias_credito']));
    }
};
