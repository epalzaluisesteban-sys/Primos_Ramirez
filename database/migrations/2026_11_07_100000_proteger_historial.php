<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Protege el historial contable:
 *  - Las llaves foráneas que borraban en cascada (al eliminar un usuario, producto, categoría
 *    o cliente se perdían compras, mermas, abonos y cierres de caja) pasan a RESTRICT.
 *  - Los productos se pueden desactivar en lugar de eliminarlos (columna `activo`).
 *  - Los cambios manuales de stock quedan registrados en `ajustes_inventario` (aparecen en el kardex).
 *  - Para los productos existentes cuyo stock no cuadra con sus movimientos (stock inicial cargado
 *    a mano, correcciones antiguas) se registra un ajuste de "saldo inicial" para que el kardex cuadre.
 */
return new class extends Migration
{
    // tabla => [columna => tabla referenciada]
    private const LLAVES = [
        'productos' => ['categoria_id' => 'categorias'],
        'compras' => ['producto_id' => 'productos', 'user_id' => 'users'],
        'mermas' => ['producto_id' => 'productos', 'user_id' => 'users'],
        'abonos' => ['cliente_id' => 'clientes', 'user_id' => 'users'],
        'cierre_cajas' => ['user_id' => 'users'],
    ];

    public function up(): void
    {
        // En MySQL los cambios de estructura no se deshacen si algo falla a mitad de camino:
        // cada paso comprueba si ya se hizo para poder volver a ejecutar la migración sin errores.
        $this->cambiarLlaves('restrict');

        if (!Schema::hasColumn('productos', 'activo')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('fecha_vencimiento');
            });
        }

        if (Schema::hasTable('ajustes_inventario')) {
            // Un intento anterior la creó pero no registró los saldos: se regeneran desde cero
            DB::table('ajustes_inventario')->whereNull('user_id')->delete();
            $this->registrarSaldosIniciales();

            return;
        }

        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->decimal('cantidad', 10, 3);        // positivo = entrada, negativo = salida
            $table->decimal('stock_anterior', 10, 3);
            $table->decimal('stock_nuevo', 10, 3);
            $table->string('motivo')->nullable();
            $table->timestamp('fecha')->useCurrent()->index();
            $table->timestamps();
        });

        $this->registrarSaldosIniciales();
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_inventario');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        $this->cambiarLlaves('cascade');
    }

    private function cambiarLlaves(string $accion): void
    {
        foreach (self::LLAVES as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $table) use ($columnas, $accion) {
                foreach ($columnas as $columna => $referencia) {
                    $table->dropForeign([$columna]);
                    $table->foreign($columna)->references('id')->on($referencia)->onDelete($accion);
                }
            });
        }
    }

    // Diferencia entre el stock guardado y lo que explican los movimientos registrados.
    private function registrarSaldosIniciales(): void
    {
        // Cada suma lleva alias: pluck() no sabe leer una expresión como SUM(tabla.columna)
        $compras = DB::table('compras')
            ->selectRaw('producto_id, SUM(cantidad) as total')
            ->groupBy('producto_id')
            ->pluck('total', 'producto_id');
        $ventas = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->where('ventas.anulada', false)
            ->selectRaw('detalle_ventas.producto_id as producto_id, SUM(detalle_ventas.cantidad) as total')
            ->groupBy('detalle_ventas.producto_id')
            ->pluck('total', 'producto_id');
        $mermas = DB::table('mermas')
            ->selectRaw('producto_id, SUM(cantidad) as total')
            ->groupBy('producto_id')
            ->pluck('total', 'producto_id');

        foreach (DB::table('productos')->get(['id', 'stock', 'created_at']) as $p) {
            $explicado = (float) ($compras[$p->id] ?? 0) - (float) ($ventas[$p->id] ?? 0) - (float) ($mermas[$p->id] ?? 0);
            $diferencia = round((float) $p->stock - $explicado, 3);

            if (abs($diferencia) < 0.0005) {
                continue;
            }

            $fecha = $p->created_at ?? now();
            DB::table('ajustes_inventario')->insert([
                'producto_id' => $p->id,
                'user_id' => null,
                'cantidad' => $diferencia,
                'stock_anterior' => 0,
                'stock_nuevo' => $diferencia,
                'motivo' => 'Saldo inicial (stock cargado antes de registrar ajustes)',
                'fecha' => $fecha,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
