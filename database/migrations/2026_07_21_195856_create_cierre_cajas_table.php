<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierre_cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('monto_apertura', 10, 2)->default(0.00);
            $table->decimal('monto_ventas_sistema', 10, 2)->default(0.00);
            $table->decimal('monto_efectivo_real', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->default(0.00); // Sobrante (+) o Faltante (-)
            $table->enum('estado', ['ABIERTA', 'CERRADA'])->default('ABIERTA');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierre_cajas');
    }
};