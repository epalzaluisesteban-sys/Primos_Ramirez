<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Una sola caja por fecha (índice único). Si ya hay fechas repetidas, se queda la primera caja de esa
 *    fecha y en sus observaciones se anotan las demás antes de quitarlas. Ninguna tabla apunta a
 *    cierre_cajas: el cuadre se calcula por fecha, así que no se pierden ventas ni abonos.
 * 2. Quién y cuándo cerró cada caja, y los gastos que se restaron del efectivo.
 * 3. Tabla de gastos del día.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->unirCajasRepetidas();

        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->unique('fecha');
            $table->decimal('monto_gastos', 10, 2)->default(0)->after('monto_ventas_sistema_ves');
            $table->decimal('monto_gastos_ves', 18, 2)->default(0)->after('monto_gastos');
            $table->foreignId('cerrada_por')->nullable()->after('estado')->constrained('users')->restrictOnDelete();
            $table->timestamp('cerrada_at')->nullable()->after('cerrada_por');
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_caja_id')->constrained('cierre_cajas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('concepto', 150);
            $table->string('categoria', 30);
            $table->decimal('monto', 10, 2);                 // equivalente en USD
            $table->string('moneda', 3)->default('USD');     // USD | VES: de qué efectivo salió
            $table->decimal('monto_moneda', 18, 2);          // lo que salió del cajón en esa moneda
            $table->decimal('tasa_cambio', 14, 4)->nullable();
            $table->timestamp('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');

        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cerrada_por');
            $table->dropColumn(['cerrada_at', 'monto_gastos', 'monto_gastos_ves']);
            $table->dropUnique(['fecha']);
        });
    }

    private function unirCajasRepetidas(): void
    {
        $fechas = DB::table('cierre_cajas')->select('fecha')->groupBy('fecha')->havingRaw('count(*) > 1')->pluck('fecha');

        foreach ($fechas as $fecha) {
            $cajas = DB::table('cierre_cajas')->where('fecha', $fecha)->orderBy('id')->get();
            $primera = $cajas->shift();

            $notas = $cajas->map(fn ($c) => sprintf(
                '[Caja repetida #%d quitada: estado %s, fondo $%s / Bs. %s, contado $%s / Bs. %s%s]',
                $c->id,
                $c->estado,
                number_format((float) $c->monto_apertura, 2, '.', ''),
                number_format((float) $c->monto_apertura_ves, 2, '.', ''),
                number_format((float) $c->monto_efectivo_real, 2, '.', ''),
                number_format((float) $c->monto_efectivo_real_ves, 2, '.', ''),
                $c->observaciones ? '. Notas: ' . $c->observaciones : ''
            ))->implode("\n");

            DB::table('cierre_cajas')->where('id', $primera->id)->update([
                'observaciones' => trim(($primera->observaciones ? $primera->observaciones . "\n" : '') . $notas),
            ]);
            DB::table('cierre_cajas')->whereIn('id', $cajas->pluck('id'))->delete();
        }
    }
};
