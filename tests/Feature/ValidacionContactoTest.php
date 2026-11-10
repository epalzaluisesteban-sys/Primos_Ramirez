<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\User;
use App\Support\Contacto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Teléfonos (solo números, 11 dígitos) y cédula/RIF (tipo + solo números) en clientes y proveedores.
 */
class ValidacionContactoTest extends TestCase
{
    use RefreshDatabase;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        $this->dueno = User::factory()->create(['rol' => 'Dueno']);
    }

    private function crearCliente(array $datos)
    {
        return $this->actingAs($this->dueno)->post(route('clientes.store'), $datos + ['nombre' => 'María González']);
    }

    public function test_telefono_con_letras_se_rechaza(): void
    {
        $this->crearCliente(['telefono' => '0412abc4567'])->assertSessionHasErrors('telefono');
        $this->assertSame(0, Cliente::count());
    }

    public function test_telefono_con_mas_o_menos_de_11_digitos_se_rechaza(): void
    {
        $this->crearCliente(['telefono' => '041212345678'])->assertSessionHasErrors('telefono');
        $this->crearCliente(['telefono' => '0412123'])->assertSessionHasErrors('telefono');
        $this->crearCliente(['telefono' => '41212345678'])->assertSessionHasErrors('telefono'); // no empieza por 0
        $this->assertSame(0, Cliente::count());
    }

    public function test_telefono_con_guiones_o_formato_internacional_se_guarda_solo_con_numeros(): void
    {
        $this->crearCliente(['telefono' => '0412-123 45.67'])->assertSessionHasNoErrors();
        $this->assertSame('04121234567', Cliente::first()->telefono);

        Cliente::query()->delete();
        $this->crearCliente(['telefono' => '+58 412 1234567'])->assertSessionHasNoErrors();
        $this->assertSame('04121234567', Cliente::first()->telefono);
    }

    public function test_telefono_es_opcional(): void
    {
        $this->crearCliente(['telefono' => ''])->assertSessionHasNoErrors();
        $this->assertNull(Cliente::first()->telefono);
    }

    public function test_cedula_con_letras_se_rechaza(): void
    {
        $this->crearCliente(['documento_tipo' => 'V', 'documento_numero' => '12A45678'])
            ->assertSessionHasErrors('documento_numero');
        $this->assertSame(0, Cliente::count());
    }

    public function test_cedula_y_rif_se_guardan_con_formato(): void
    {
        $this->crearCliente(['documento_tipo' => 'V', 'documento_numero' => '12.345.678'])->assertSessionHasNoErrors();
        $this->assertSame('V-12345678', Cliente::first()->rif);

        Cliente::query()->delete();
        $this->crearCliente(['documento_tipo' => 'J', 'documento_numero' => '123456789'])->assertSessionHasNoErrors();
        $this->assertSame('J-12345678-9', Cliente::first()->rif);
    }

    public function test_rif_de_empresa_necesita_9_digitos(): void
    {
        $this->crearCliente(['documento_tipo' => 'J', 'documento_numero' => '12345678'])
            ->assertSessionHasErrors('documento_numero');
    }

    public function test_tipo_de_documento_invalido_se_rechaza(): void
    {
        $this->crearCliente(['documento_tipo' => 'X', 'documento_numero' => '12345678'])
            ->assertSessionHasErrors('documento_tipo');
    }

    public function test_editar_cliente_valida_telefono(): void
    {
        $cliente = Cliente::create(['nombre' => 'Pedro', 'telefono' => '0412-1234567', 'rif' => 'V-1234567']);

        $this->actingAs($this->dueno)
            ->put(route('clientes.update', $cliente), ['nombre' => 'Pedro', 'telefono' => 'cuatro', 'activo' => 1])
            ->assertSessionHasErrors('telefono');

        $this->actingAs($this->dueno)
            ->put(route('clientes.update', $cliente), [
                'nombre' => 'Pedro', 'telefono' => '0414-7654321', 'documento_tipo' => 'E', 'documento_numero' => '8123456', 'activo' => 1,
            ])
            ->assertSessionHasNoErrors();

        $cliente->refresh();
        $this->assertSame('04147654321', $cliente->telefono);
        $this->assertSame('E-8123456', $cliente->rif);
    }

    public function test_proveedor_valida_telefono_y_whatsapp(): void
    {
        $this->actingAs($this->dueno)
            ->post(route('proveedores.store'), ['nombre' => 'Distribuidora', 'telefono' => '0212abcdefg', 'whatsapp' => '0412123456789'])
            ->assertSessionHasErrors(['telefono', 'whatsapp']);
        $this->assertSame(0, Proveedor::count());

        $this->actingAs($this->dueno)
            ->post(route('proveedores.store'), ['nombre' => 'Distribuidora', 'telefono' => '0212-5551234', 'whatsapp' => '0412-1234567'])
            ->assertSessionHasNoErrors();

        $proveedor = Proveedor::first();
        $this->assertSame('02125551234', $proveedor->telefono);
        $this->assertSame('https://wa.me/584121234567', $proveedor->whatsappUrl());
    }

    public function test_formularios_solo_aceptan_numeros_en_telefono_y_cedula(): void
    {
        Http::fake();

        $this->actingAs($this->dueno)->get(route('clientes.create'))
            ->assertOk()
            ->assertSee('name="telefono"', false)
            ->assertSee('maxlength="11" pattern="0[0-9]{10}"', false)
            ->assertSee('oninput="soloDigitos(this, 11, true)"', false)
            ->assertSee('name="documento_tipo"', false)
            ->assertSee('oninput="soloDigitos(this, 9)"', false);

        // Al editar, un teléfono y RIF guardados con guiones se cargan solo con números
        $cliente = Cliente::create(['nombre' => 'Pedro', 'telefono' => '0412-1234567', 'rif' => 'J-12345678-9']);
        $this->actingAs($this->dueno)->get(route('clientes.edit', $cliente))
            ->assertOk()
            ->assertSee('value="04121234567"', false)
            ->assertSee('value="123456789"', false)
            ->assertSee('<option value="J" selected>', false);

        $this->actingAs($this->dueno)->get(route('proveedores.create'))
            ->assertOk()
            ->assertSee('name="whatsapp"', false)
            ->assertSee('maxlength="11"', false);
    }

    public function test_ayudantes_de_formato(): void
    {
        $this->assertSame('0412-1234567', Contacto::telefono('04121234567'));
        $this->assertSame('texto viejo', Contacto::telefono('texto viejo'));
        $this->assertSame(['V', '12345678'], Contacto::separarDocumento('v-12.345.678'));
        $this->assertSame(['J', '123456789'], Contacto::separarDocumento('J-12345678-9'));
        $this->assertNull(Contacto::unirDocumento('V', ''));
        // WhatsApp guardado antes con código de país sigue funcionando
        $this->assertSame('584121234567', Contacto::telefonoWhatsapp('58 412 1234567'));
    }
}
