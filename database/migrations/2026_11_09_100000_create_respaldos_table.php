<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de respaldos (Administración → Respaldo y restauración).
 * No forma parte del respaldo y no tiene llave foránea a users: al restaurar se reemplazan los usuarios,
 * así que el autor se guarda como texto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respaldos', function (Blueprint $table) {
            $table->id();
            $table->string('archivo');                       // nombre del .tar.gz en storage/app/private/respaldos
            $table->string('tipo', 20)->default('manual');   // manual | automatico (antes de restaurar)
            $table->string('autor');
            $table->string('nota')->nullable();
            $table->unsignedBigInteger('tamano')->default(0); // bytes
            $table->unsignedInteger('registros')->default(0);
            $table->unsignedInteger('fotos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respaldos');
    }
};
