<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('nombre');
            $table->string('marca')->nullable()->after('foto');
            $table->string('ubicacion')->nullable()->after('marca');
            $table->string('codigo_barras')->nullable()->after('ubicacion');
            $table->string('codigo_qr')->nullable()->after('codigo_barras');
            $table->decimal('precio_compra', 10, 2)->nullable()->after('precio');
            $table->integer('stock_maximo')->nullable()->after('stock_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['foto', 'marca', 'ubicacion', 'codigo_barras', 'codigo_qr', 'precio_compra', 'stock_maximo']);
        });
    }
};
