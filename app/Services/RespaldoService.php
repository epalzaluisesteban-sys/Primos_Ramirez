<?php

namespace App\Services;

use App\Exceptions\RespaldoInvalido;
use App\Models\CierreCaja;
use App\Models\Respaldo;
use App\Support\Almacen;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PharData;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Respaldo y restauración de toda la información del negocio.
 *
 * El respaldo es UN archivo .json de texto plano (se abre con el Bloc de notas):
 *   sistema, formato, creado_en, creado_por…  datos del respaldo
 *   registros   cantidad de filas por tabla (para comprobar que el archivo está completo)
 *   columnas    columnas de cada tabla al momento del respaldo
 *   tablas      las filas de cada tabla, sin depender del motor: un respaldo de MySQL (local)
 *               se puede restaurar en PostgreSQL (Render/Supabase) y al revés
 *   fotos       al final, las fotos que usan los registros, convertidas a texto (base64)
 *
 * También se restauran los respaldos .tar.gz del formato 1 (los primeros que generó el sistema).
 * Restaurar reemplaza TODO dentro de una transacción (si algo falla no cambia nada) y antes guarda
 * automáticamente un respaldo del estado actual.
 */
class RespaldoService
{
    public const SISTEMA = 'primos-ramirez';
    public const FORMATO = 2;              // 1 = .tar.gz (solo lectura), 2 = .json de texto plano
    // Archivos subidos para restaurar: siempre en el disco local, porque se leen por ruta y solo duran hasta confirmar
    public const DISCO_PENDIENTES = 'local';
    public const CARPETA = 'respaldos';
    public const DIAS_AVISO = 7;           // el Inicio avisa al Dueño si pasa más tiempo sin respaldar

    // Orden de inserción: cada tabla después de las que referencia. Para borrar se recorre al revés.
    // Lista fija a propósito: la conexión puede ver tablas de otros sistemas (phpMyAdmin, otros proyectos).
    public const TABLAS = [
        'users' => 'Usuarios',
        'categorias' => 'Categorías',
        'proveedores' => 'Proveedores',
        'clientes' => 'Clientes',
        'tasas_cambio' => 'Tasas BCV',
        'productos' => 'Productos',
        'compras' => 'Compras',
        'ventas' => 'Ventas',
        'detalle_ventas' => 'Productos vendidos',
        'abonos' => 'Abonos',
        'mermas' => 'Pérdidas',
        'ajustes_inventario' => 'Ajustes de inventario',
        'cierre_cajas' => 'Cierres de caja',
    ];

    // Columna con la ruta de la foto en el disco de fotos (App\Support\Almacen)
    private const COLUMNAS_FOTO = ['users' => 'foto', 'categorias' => 'imagen', 'clientes' => 'foto', 'productos' => 'foto'];
    private const CARPETAS_FOTO = ['usuarios', 'categorias', 'clientes', 'productos'];

    /** Disco de los respaldos guardados (config filesystems.respaldos_disk). Siempre privado. */
    public static function disco(): string
    {
        return config('filesystems.respaldos_disk');
    }

    /** Crea un respaldo, lo guarda en el servidor y lo registra en el historial. */
    public function crear(string $autor, string $tipo = 'manual', ?string $nota = null): Respaldo
    {
        $candado = $this->candado();

        try {
            return $this->generar($autor, $tipo, $nota);
        } finally {
            $candado->release();
        }
    }

    /**
     * Abre y valida un respaldo sin tocar la base de datos.
     *
     * @return array{manifiesto: array, datos: array<string, array>, fotos: array<string, string>}
     */
    public function leer(string $rutaArchivo): array
    {
        // El .json empieza con "{" (quizá tras un BOM o espacios si alguien lo abrió y guardó con el Bloc de notas)
        $inicio = ltrim((string) file_get_contents($rutaArchivo, false, null, 0, 64), "\xEF\xBB\xBF \t\r\n");
        $respaldo = str_starts_with($inicio, '{') ? $this->leerJson($rutaArchivo) : $this->leerTarGz($rutaArchivo);

        $manifiesto = $respaldo['manifiesto'];
        foreach (array_keys(self::TABLAS) as $tabla) {
            $filas = $respaldo['datos'][$tabla] ?? null;
            if (!is_array($filas) || !array_is_list($filas) || count($filas) !== ($manifiesto['tablas'][$tabla] ?? -1)) {
                throw new RespaldoInvalido('El respaldo está incompleto o fue modificado (' . self::TABLAS[$tabla] . '). Usa otro archivo.');
            }

            $columnas = Schema::getColumnListing($tabla);
            if (array_diff($manifiesto['columnas'][$tabla] ?? [], $columnas)) {
                throw new RespaldoInvalido('Este respaldo es de una versión más nueva del sistema. Actualiza el sistema antes de restaurarlo.');
            }

            // El .json se puede abrir y editar a mano: cada fila debe tener solo columnas que existen y valores simples
            foreach ($filas as $fila) {
                if (!is_array($fila) || array_is_list($fila) || array_diff(array_keys($fila), $columnas)
                    || array_filter($fila, fn ($valor) => is_array($valor))) {
                    throw new RespaldoInvalido('El respaldo fue modificado y tiene datos que no se pueden restaurar (' . self::TABLAS[$tabla] . '). Usa otro archivo.');
                }
            }
        }

        return $respaldo;
    }

    /** Formato 2: un solo .json de texto plano. */
    private function leerJson(string $rutaArchivo): array
    {
        $contenido = json_decode(ltrim((string) file_get_contents($rutaArchivo), "\xEF\xBB\xBF"), true);
        if (!is_array($contenido) || ($contenido['sistema'] ?? null) !== self::SISTEMA) {
            throw new RespaldoInvalido('El archivo no es un respaldo de este sistema o está dañado.');
        }
        if (($contenido['formato'] ?? null) !== self::FORMATO) {
            throw new RespaldoInvalido('Este respaldo se hizo con otra versión del sistema y no se puede restaurar aquí.');
        }

        $fotos = [];
        foreach ((array) ($contenido['fotos'] ?? []) as $ruta => $base64) {
            $binario = is_string($base64) ? base64_decode($base64, true) : false;
            if ($binario === false || !$this->rutaFotoValida((string) $ruta)) {
                throw new RespaldoInvalido('Una de las fotos del respaldo está dañada. Usa otro archivo.');
            }
            $fotos[$ruta] = $binario;
        }

        $manifiesto = collect($contenido)->except(['tablas', 'fotos', 'registros'])->all();
        $manifiesto['tablas'] = (array) ($contenido['registros'] ?? []);
        $manifiesto['fotos'] = count($fotos);

        return ['manifiesto' => $manifiesto, 'datos' => (array) ($contenido['tablas'] ?? []), 'fotos' => $fotos];
    }

    /** Formato 1: .tar.gz con manifiesto.json, datos/<tabla>.json y fotos/<ruta>. Solo se lee. */
    private function leerTarGz(string $rutaArchivo): array
    {
        // PharData reconoce el formato por la extensión: el archivo subido llega sin ella
        $copia = $this->rutaTemporal('.tar.gz');
        copy($rutaArchivo, $copia);

        try {
            try {
                $archivo = new PharData($copia);
            } catch (Throwable) {
                throw new RespaldoInvalido('El archivo no es un respaldo de este sistema o está dañado.');
            }

            $manifiesto = json_decode($this->contenido($archivo, 'manifiesto.json') ?? '', true);
            if (!is_array($manifiesto) || ($manifiesto['sistema'] ?? null) !== self::SISTEMA) {
                throw new RespaldoInvalido('El archivo no es un respaldo de este sistema.');
            }
            if (($manifiesto['formato'] ?? null) !== 1) {
                throw new RespaldoInvalido('Este respaldo se hizo con otra versión del sistema y no se puede restaurar aquí.');
            }

            $datos = [];
            foreach (array_keys(self::TABLAS) as $tabla) {
                $datos[$tabla] = json_decode($this->contenido($archivo, "datos/{$tabla}.json") ?? '', true);
            }

            $fotos = [];
            foreach (new RecursiveIteratorIterator($archivo) as $entrada) {
                $interna = str_replace('\\', '/', substr($entrada->getPathname(), strlen('phar://' . $copia) + 1));
                if (str_starts_with($interna, 'fotos/')) {
                    $ruta = substr($interna, strlen('fotos/'));
                    if ($this->rutaFotoValida($ruta)) {
                        $fotos[$ruta] = $entrada->getContent();
                    }
                }
            }

            return ['manifiesto' => $manifiesto, 'datos' => $datos, 'fotos' => $fotos];
        } finally {
            unset($archivo, $entrada);
            $this->borrarTemporal($copia);
        }
    }

    /** Cantidad de registros por tabla: en el respaldo y en la base actual (pantalla de confirmación). */
    public function comparar(array $respaldo): array
    {
        $filas = [];
        foreach (self::TABLAS as $tabla => $nombre) {
            $filas[] = [
                'nombre' => $nombre,
                'respaldo' => count($respaldo['datos'][$tabla]),
                'actual' => DB::table($tabla)->count(),
            ];
        }

        return $filas;
    }

    /** Lo que entraría hoy en un respaldo: registros de todas las tablas y fotos en uso. */
    public function resumenActual(): array
    {
        $registros = 0;
        foreach (array_keys(self::TABLAS) as $tabla) {
            $registros += DB::table($tabla)->count();
        }

        $fotos = 0;
        foreach (self::COLUMNAS_FOTO as $tabla => $columna) {
            $fotos += DB::table($tabla)->whereNotNull($columna)->where($columna, '!=', '')->count();
        }

        return ['registros' => $registros, 'fotos' => $fotos];
    }

    /**
     * Reemplaza toda la información por la del respaldo. Antes guarda un respaldo automático del estado actual.
     *
     * @return Respaldo el respaldo automático previo
     */
    public function restaurar(string $rutaArchivo, string $autor): Respaldo
    {
        $candado = $this->candado();

        try {
            $respaldo = $this->leer($rutaArchivo);
            $fecha = $respaldo['manifiesto']['creado_en'] ?? '';
            $previo = $this->generar($autor, 'automatico', 'Antes de restaurar el respaldo del ' . $this->fechaLegible($fecha));

            // Mientras dura, el resto del sistema no acepta cambios (middleware BloquearDuranteRestauracion)
            Cache::put('restauracion_en_curso', true, now()->addMinutes(5));

            try {
                DB::transaction(function () use ($respaldo) {
                    foreach (array_reverse(array_keys(self::TABLAS)) as $tabla) {
                        DB::table($tabla)->delete();
                    }
                    foreach (array_keys(self::TABLAS) as $tabla) {
                        $booleanas = $this->columnasBooleanas($tabla);
                        foreach (array_chunk($respaldo['datos'][$tabla], 200) as $lote) {
                            DB::table($tabla)->insert(array_map(fn ($fila) => $this->normalizar($fila, $booleanas), $lote));
                        }
                    }
                    $this->ajustarContadores();
                });
            } catch (QueryException $e) {
                // Datos que la base no acepta (p. ej. un .json editado a mano con ids repetidos): la transacción
                // ya deshizo todo. Se registra el detalle técnico y al usuario se le explica qué pasó.
                report($e);
                throw new RespaldoInvalido('La base de datos no aceptó la información del respaldo, así que no se cambió nada. Usa otro archivo.');
            }

            // Las fotos se escriben al final: si la transacción falló no se toca nada.
            // No se borran fotos existentes: las que ya no use nadie quedan sin efecto.
            $disco = Storage::disk(Almacen::disco());
            foreach ($respaldo['fotos'] as $ruta => $contenido) {
                $disco->put($ruta, $contenido);
            }

            CierreCaja::olvidarEstados();

            return $previo;
        } finally {
            Cache::forget('restauracion_en_curso');
            $candado->release();
        }
    }

    public function fechaLegible(?string $iso): string
    {
        try {
            return \Illuminate\Support\Carbon::parse($iso)->format('d/m/Y h:i A');
        } catch (Throwable) {
            return 'fecha desconocida';
        }
    }

    // ---------------------------------------------------------------------------------------------

    private function generar(string $autor, string $tipo, ?string $nota): Respaldo
    {
        $momento = now();
        $nombre = 'respaldo-primos-ramirez-' . $momento->format('Y-m-d-His') . ($tipo === 'automatico' ? '-automatico' : '') . '.json';
        $tablas = [];
        $conteos = [];
        $columnas = [];
        $rutasFotos = [];

        foreach (array_keys(self::TABLAS) as $tabla) {
            $tablas[$tabla] = DB::table($tabla)->orderBy('id')->get()->map(fn ($f) => (array) $f)->all();
            $conteos[$tabla] = count($tablas[$tabla]);
            $columnas[$tabla] = Schema::getColumnListing($tabla);

            if ($columna = self::COLUMNAS_FOTO[$tabla] ?? null) {
                foreach ($tablas[$tabla] as $fila) {
                    if (!empty($fila[$columna])) {
                        $rutasFotos[] = $fila[$columna];
                    }
                }
            }
        }

        $disco = Storage::disk(Almacen::disco());
        $fotos = [];
        foreach (array_unique($rutasFotos) as $ruta) {
            if ($this->rutaFotoValida($ruta) && $disco->exists($ruta)) {
                $fotos[$ruta] = base64_encode($disco->get($ruta));
            }
        }

        // Orden pensado para leerlo: primero de qué es el respaldo, luego los datos y al final las fotos
        $contenido = json_encode([
            'sistema' => self::SISTEMA,
            'formato' => self::FORMATO,
            'descripcion' => 'Respaldo de Los Primos Ramírez. Se restaura desde Administración → Respaldo y restauración. '
                . 'Si lo modificas a mano, el sistema puede rechazarlo.',
            'creado_en' => $momento->toIso8601String(),
            'creado_por' => $autor,
            'tipo' => $tipo,
            'motor' => DB::getDriverName(),
            'migracion' => DB::table('migrations')->orderByDesc('id')->value('migration'),
            'registros' => $conteos,
            'columnas' => $columnas,
            'tablas' => $tablas,
            'fotos' => (object) $fotos,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);

        Storage::disk(self::disco())->put(self::CARPETA . '/' . $nombre, $contenido);

        return Respaldo::create([
            'archivo' => $nombre,
            'tipo' => $tipo,
            'autor' => $autor,
            'nota' => $nota,
            'tamano' => strlen($contenido),
            'registros' => array_sum($conteos),
            'fotos' => count($fotos),
        ]);
    }

    private function candado(): Lock
    {
        $candado = Cache::lock('respaldos', 300);
        if (!$candado->get()) {
            throw new RespaldoInvalido('Ya se está haciendo un respaldo o una restauración. Espera un momento e inténtalo de nuevo.');
        }

        return $candado;
    }

    private function contenido(PharData $archivo, string $interna): ?string
    {
        return isset($archivo[$interna]) ? $archivo[$interna]->getContent() : null;
    }

    // Solo rutas como "productos/abc123.webp": nada de "..", rutas absolutas ni carpetas ajenas
    private function rutaFotoValida(string $ruta): bool
    {
        $carpeta = Str::before($ruta, '/');

        return in_array($carpeta, self::CARPETAS_FOTO, true)
            && !str_contains($ruta, '..')
            && preg_match('#^[a-z]+/[A-Za-z0-9._-]+$#', $ruta) === 1;
    }

    // PostgreSQL no acepta 1/0 en columnas boolean (vienen así de un respaldo hecho en MySQL)
    private function columnasBooleanas(string $tabla): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return [];
        }

        return collect(Schema::getColumns($tabla))
            ->filter(fn ($c) => in_array(strtolower($c['type_name']), ['bool', 'boolean'], true))
            ->pluck('name')
            ->all();
    }

    private function normalizar(array $fila, array $booleanas): array
    {
        foreach ($fila as $columna => $valor) {
            if (in_array($columna, $booleanas, true) && $valor !== null) {
                $fila[$columna] = (bool) $valor;
            } elseif (is_bool($valor) && !$booleanas) {
                // Respaldo de PostgreSQL restaurado en MySQL/SQLite
                $fila[$columna] = (int) $valor;
            }
        }

        return $fila;
    }

    // En PostgreSQL los ids se insertan a mano: hay que mover cada secuencia al mayor id, o la próxima
    // venta chocaría con una restaurada. MySQL y SQLite lo ajustan solos.
    private function ajustarContadores(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::TABLAS) as $tabla) {
            DB::statement("SELECT setval(pg_get_serial_sequence('{$tabla}', 'id'), COALESCE((SELECT MAX(id) FROM {$tabla}), 1), (SELECT MAX(id) FROM {$tabla}) IS NOT NULL)");
        }
    }

    private function rutaTemporal(string $extension): string
    {
        return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'respaldo-' . Str::uuid() . $extension;
    }

    private function borrarTemporal(string $ruta): void
    {
        if (!file_exists($ruta)) {
            return;
        }
        try {
            // Libera el archivo que Phar mantiene abierto (en Windows no se puede borrar si sigue abierto)
            PharData::unlinkArchive($ruta);
        } catch (Throwable) {
            @unlink($ruta);
        }
    }
}
