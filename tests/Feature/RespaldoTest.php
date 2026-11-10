<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Respaldo;
use App\Models\User;
use App\Models\Venta;
use App\Services\RespaldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RespaldoTest extends TestCase
{
    use RefreshDatabase;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        CierreCaja::olvidarEstados();

        $this->dueno = User::factory()->create(['rol' => 'Dueno', 'email' => 'dueno@prueba.test', 'foto' => 'usuarios/dueno.png']);
        Storage::disk('public')->put('usuarios/dueno.png', 'foto-del-dueno');

        $categoria = Categoria::create(['nombre' => 'Frutas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Cambur', 'unidad_medida' => 'kg',
            'precio' => 2, 'stock' => 10, 'stock_minimo' => 1,
        ]);
        $cliente = Cliente::create(['nombre' => 'Ana', 'limite_credito' => 50]);
        $venta = Venta::create([
            'user_id' => $this->dueno->id, 'cliente_id' => $cliente->id, 'fecha' => now(),
            'total' => 4, 'tipo_pago' => 'credito', 'moneda_pago' => 'USD',
        ]);
        DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 2, 'subtotal' => 4]);
    }

    /**
     * Todas las filas de las tablas respaldadas, para comparar antes y después.
     * Sin remember_token: al cerrar la sesión tras restaurar, Laravel lo renueva a propósito.
     */
    private function foto(): array
    {
        $datos = [];
        foreach (array_keys(RespaldoService::TABLAS) as $tabla) {
            $datos[$tabla] = DB::table($tabla)->orderBy('id')->get()
                ->map(fn ($f) => collect((array) $f)->except('remember_token')->all())->all();
        }

        return $datos;
    }

    private function crearRespaldo(): Respaldo
    {
        $this->actingAs($this->dueno)->post(route('respaldos.store'))
            ->assertRedirect(route('respaldos.index'))
            ->assertSessionHas('descargar');

        return Respaldo::latest('id')->first();
    }

    private function archivoDe(Respaldo $respaldo): UploadedFile
    {
        return new UploadedFile(Storage::disk('local')->path($respaldo->ruta()), $respaldo->archivo, 'application/json', null, true);
    }

    private function restaurarConfirmando(string $pendiente): void
    {
        $this->actingAs($this->dueno)->post(route('respaldos.restaurar', $pendiente), [
            'confirmacion' => 'RESTAURAR',
            'password' => 'password',
        ])->assertRedirect(route('login'));
    }

    /** Respaldo con el formato 1 (.tar.gz) que generaba el sistema antes, armado con los datos actuales. */
    private function respaldoFormatoAntiguo(): UploadedFile
    {
        $tar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'respaldo-prueba-' . uniqid() . '.tar';
        $archivo = new \PharData($tar);
        $conteos = [];
        $columnas = [];
        foreach (array_keys(RespaldoService::TABLAS) as $tabla) {
            $filas = DB::table($tabla)->orderBy('id')->get()->map(fn ($f) => (array) $f)->all();
            $conteos[$tabla] = count($filas);
            $columnas[$tabla] = \Illuminate\Support\Facades\Schema::getColumnListing($tabla);
            $archivo->addFromString("datos/{$tabla}.json", json_encode($filas));
        }
        $archivo->addFromString('fotos/usuarios/dueno.png', 'foto-del-dueno');
        $archivo->addFromString('manifiesto.json', json_encode([
            'sistema' => 'primos-ramirez', 'formato' => 1, 'creado_en' => now()->toIso8601String(),
            'creado_por' => 'Formato antiguo', 'tablas' => $conteos, 'columnas' => $columnas, 'fotos' => 1,
        ]));
        $archivo->compress(\Phar::GZ);
        unset($archivo);
        \PharData::unlinkArchive($tar);

        $destino = Storage::disk('local')->path('respaldo-antiguo.tar.gz');
        @mkdir(dirname($destino), 0777, true);
        rename(substr($tar, 0, -4) . '.tar.gz', $destino);

        return new UploadedFile($destino, 'respaldo-antiguo.tar.gz', 'application/gzip', null, true);
    }

    public function test_el_respaldo_es_un_json_de_texto_plano_legible(): void
    {
        $respaldo = $this->crearRespaldo();
        $this->assertStringEndsWith('.json', $respaldo->archivo);

        $texto = Storage::disk('local')->get($respaldo->ruta());
        $this->assertStringStartsWith('{', $texto);
        $this->assertStringContainsString("\n", $texto); // con saltos de línea: se lee en el Bloc de notas

        $json = json_decode($texto, true);
        $this->assertSame('primos-ramirez', $json['sistema']);
        $this->assertSame('Ana', $json['tablas']['clientes'][0]['nombre']);
        $this->assertSame(1, $json['registros']['ventas']);
        $this->assertSame('foto-del-dueno', base64_decode($json['fotos']['usuarios/dueno.png']));
        $this->assertSame('fotos', array_key_last($json)); // las fotos van al final
    }

    public function test_un_respaldo_tar_gz_del_formato_anterior_se_sigue_restaurando(): void
    {
        $antes = $this->foto();
        $archivo = $this->respaldoFormatoAntiguo();

        Cliente::create(['nombre' => 'Registrado después']);
        Storage::disk('public')->delete('usuarios/dueno.png');

        $this->restaurarConfirmando($this->subir($archivo));

        $this->assertEquals($antes, $this->foto());
        Storage::disk('public')->assertExists('usuarios/dueno.png');
    }

    public function test_un_json_editado_con_datos_que_no_existen_se_rechaza_sin_tocar_nada(): void
    {
        $respaldo = $this->crearRespaldo();
        $json = json_decode(Storage::disk('local')->get($respaldo->ruta()), true);
        $json['tablas']['clientes'][0]['columna_inventada'] = 'x';
        Storage::disk('local')->put($respaldo->ruta(), json_encode($json));
        $antes = $this->foto();

        $this->actingAs($this->dueno)->post(route('respaldos.preparar'), ['archivo' => $this->archivoDe($respaldo)])
            ->assertSessionHasErrors('archivo');

        $json['tablas']['clientes'][0] = collect($json['tablas']['clientes'][0])->except('columna_inventada')->all();
        $json['fotos']['usuarios/dueno.png'] = 'esto no es base64 válido!!';
        Storage::disk('local')->put($respaldo->ruta(), json_encode($json));

        $this->actingAs($this->dueno)->post(route('respaldos.preparar'), ['archivo' => $this->archivoDe($respaldo)])
            ->assertSessionHasErrors('archivo');

        $this->assertEquals($antes, $this->foto());
    }

    private function subir(UploadedFile $archivo): string
    {
        $respuesta = $this->actingAs($this->dueno)->post(route('respaldos.preparar'), ['archivo' => $archivo]);
        $respuesta->assertSessionHasNoErrors();

        return basename($respuesta->headers->get('Location'));
    }

    public function test_crear_y_restaurar_deja_la_informacion_y_las_fotos_como_estaban(): void
    {
        $antes = $this->foto();
        $respaldo = $this->crearRespaldo();
        $this->assertSame(1, $respaldo->fotos);
        $this->actingAs($this->dueno)->get(route('respaldos.descargar', $respaldo))->assertOk()->assertDownload($respaldo->archivo);

        // Cambios posteriores que la restauración debe deshacer
        Cliente::create(['nombre' => 'Cliente nuevo']);
        Producto::first()->update(['precio' => 99]);
        DB::table('detalle_ventas')->delete();
        Storage::disk('public')->delete('usuarios/dueno.png');

        $pendiente = $this->subir($this->archivoDe($respaldo));
        $this->actingAs($this->dueno)->get(route('respaldos.confirmar', $pendiente))
            ->assertOk()
            ->assertSee('Confirmar restauración')
            ->assertSee('dueno@prueba.test');

        $this->actingAs($this->dueno)->post(route('respaldos.restaurar', $pendiente), [
            'confirmacion' => 'restaurar',
            'password' => 'password',
        ])->assertRedirect(route('login'));

        $this->assertEquals($antes, $this->foto());
        Storage::disk('public')->assertExists('usuarios/dueno.png');
        $this->assertGuest();

        // Antes de restaurar se guardó automáticamente lo que había (con el cliente nuevo)
        $automatico = Respaldo::where('tipo', 'automatico')->first();
        $this->assertNotNull($automatico);
        Storage::disk('local')->assertExists($automatico->ruta());

        // Después de restaurar se puede seguir registrando
        $venta = Venta::create(['user_id' => $this->dueno->id, 'fecha' => now(), 'total' => 1, 'tipo_pago' => 'contado', 'moneda_pago' => 'USD']);
        $this->assertGreaterThan(Venta::where('id', '<', $venta->id)->max('id') ?? 0, $venta->id);
    }

    public function test_archivo_que_no_es_respaldo_se_rechaza_sin_tocar_nada(): void
    {
        $antes = $this->foto();

        $this->actingAs($this->dueno)->post(route('respaldos.preparar'), [
            'archivo' => UploadedFile::fake()->createWithContent('respaldo.tar.gz', 'esto no es un respaldo'),
        ])->assertSessionHasErrors('archivo');

        $this->assertEquals($antes, $this->foto());
        $this->assertSame([], Storage::disk('local')->allFiles(RespaldoService::CARPETA . '/pendientes'));
    }

    public function test_sin_escribir_restaurar_o_con_contrasena_incorrecta_no_cambia_nada(): void
    {
        $pendiente = $this->subir($this->archivoDe($this->crearRespaldo()));
        Cliente::create(['nombre' => 'Registrado después']);
        $antes = $this->foto();

        $this->actingAs($this->dueno)->post(route('respaldos.restaurar', $pendiente), ['confirmacion' => 'si', 'password' => 'password'])
            ->assertSessionHasErrors('confirmacion');
        $this->actingAs($this->dueno)->post(route('respaldos.restaurar', $pendiente), ['confirmacion' => 'RESTAURAR', 'password' => 'otra'])
            ->assertSessionHasErrors('password');

        $this->assertEquals($antes, $this->foto());
        $this->assertSame(0, Respaldo::where('tipo', 'automatico')->count());
    }

    public function test_solo_el_dueno_puede_usar_respaldos(): void
    {
        $respaldo = $this->crearRespaldo();

        foreach (['Encargado', 'Obrero'] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol]);
            $this->actingAs($usuario)->get(route('respaldos.index'))->assertForbidden();
            $this->actingAs($usuario)->post(route('respaldos.store'))->assertForbidden();
            $this->actingAs($usuario)->get(route('respaldos.descargar', $respaldo))->assertForbidden();
        }
    }

    public function test_mientras_se_restaura_no_se_aceptan_cambios(): void
    {
        Cache::put('restauracion_en_curso', true, 60);

        $this->actingAs($this->dueno)->from(route('clientes.index'))
            ->post(route('clientes.store'), ['nombre' => 'Durante la restauración'])
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseMissing('clientes', ['nombre' => 'Durante la restauración']);
    }
}
