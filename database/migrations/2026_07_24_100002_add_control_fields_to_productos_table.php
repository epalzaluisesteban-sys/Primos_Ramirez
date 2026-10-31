<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Unidad en la que se maneja el producto (clave para frutas/verduras: kg, atado, caja, etc.)
            $table->string('unidad_medida', 20)->default('unidad')->after('nombre');

            // Umbral individual de stock crítico (reemplaza el "≤ 5" fijo del dashboard)
            $table->integer('stock_minimo')->default(5)->after('stock');

            // Fecha de vencimiento del lote actual en anaquel (opcional, útil para perecederos)
            $table->date('fecha_vencimiento')->nullable()->after('stock_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['unidad_medida', 'stock_minimo', 'fecha_vencimiento']);
        });
    }
};
