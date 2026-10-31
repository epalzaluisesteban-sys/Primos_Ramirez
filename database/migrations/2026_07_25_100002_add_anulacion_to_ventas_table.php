<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('anulada')->default(false)->after('total');
            $table->timestamp('anulada_at')->nullable()->after('anulada');
            $table->string('motivo_anulacion')->nullable()->after('anulada_at');
            $table->boolean('editada')->default(false)->after('motivo_anulacion');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['anulada', 'anulada_at', 'motivo_anulacion', 'editada']);
        });
    }
};
