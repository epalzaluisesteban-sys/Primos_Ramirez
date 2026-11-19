<?php

use App\Support\Unicidad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nada se registra dos veces:
 * - productos: nombre + marca normalizados por unidad, y código de barras;
 * - categorías y proveedores: nombre normalizado; proveedores también RIF (columna nueva);
 * - clientes: cédula/RIF.
 * Los repetidos que ya existían se corrigen con App\Support\Unicidad sin borrar nada ni tocar el historial.
 */
return new class extends Migration
{
    private const TABLAS = ['categorias', 'proveedores', 'productos', 'clientes'];

    public function up(): void
    {
        foreach (['productos', 'categorias', 'proveedores'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->string('nombre_normalizado')->nullable()->after('nombre'));
        }
        Schema::table('proveedores', fn (Blueprint $t) => $t->string('rif', 20)->nullable()->after('contacto'));

        foreach (self::TABLAS as $tabla) {
            $filas = DB::table($tabla)->orderBy('id')->get()->map(fn ($f) => (array) $f)->all();
            foreach (Unicidad::corregir($tabla, $filas) as $i => $corregida) {
                $cambios = array_filter($corregida, fn ($valor, $columna) => $valor !== $filas[$i][$columna], ARRAY_FILTER_USE_BOTH);
                if ($cambios) {
                    DB::table($tabla)->where('id', $corregida['id'])->update($cambios);
                }
            }
        }

        Schema::table('productos', function (Blueprint $t) {
            $t->unique(['nombre_normalizado', 'unidad_medida']);
            $t->unique('codigo_barras');
        });
        Schema::table('categorias', fn (Blueprint $t) => $t->unique('nombre_normalizado'));
        Schema::table('proveedores', function (Blueprint $t) {
            $t->unique('nombre_normalizado');
            $t->unique('rif');
        });
        Schema::table('clientes', fn (Blueprint $t) => $t->unique('rif'));
    }

    public function down(): void
    {
        Schema::table('clientes', fn (Blueprint $t) => $t->dropUnique(['rif']));
        Schema::table('proveedores', function (Blueprint $t) {
            $t->dropUnique(['rif']);
            $t->dropUnique(['nombre_normalizado']);
            $t->dropColumn(['rif', 'nombre_normalizado']);
        });
        Schema::table('categorias', function (Blueprint $t) {
            $t->dropUnique(['nombre_normalizado']);
            $t->dropColumn('nombre_normalizado');
        });
        Schema::table('productos', function (Blueprint $t) {
            $t->dropUnique(['codigo_barras']);
            $t->dropUnique(['nombre_normalizado', 'unidad_medida']);
            $t->dropColumn('nombre_normalizado');
        });
    }
};
