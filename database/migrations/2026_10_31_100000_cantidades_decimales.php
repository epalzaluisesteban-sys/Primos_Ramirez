<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Productos por peso (Kg, Lb, Litro) necesitan cantidades fraccionarias (p. ej. 2.5 Kg).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('stock', 10, 3)->default(0)->change();
            $table->decimal('stock_minimo', 10, 3)->default(5)->change();
            $table->decimal('stock_maximo', 10, 3)->nullable()->change();
        });

        foreach (['detalle_ventas', 'compras', 'mermas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->decimal('cantidad', 10, 3)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
            $table->integer('stock_minimo')->default(5)->change();
            $table->integer('stock_maximo')->nullable()->change();
        });

        foreach (['detalle_ventas', 'compras', 'mermas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->integer('cantidad')->change();
            });
        }
    }
};
