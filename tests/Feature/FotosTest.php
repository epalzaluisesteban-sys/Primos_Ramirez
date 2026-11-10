<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Las fotos subidas deben poder verse aunque falte el enlace public/storage
 * (pasa al copiar el proyecto a otra computadora en Windows): el disco "public" las sirve por /storage/{ruta}.
 */
class FotosTest extends TestCase
{
    use RefreshDatabase;

    // PNG real de 1x1 píxel: UploadedFile::fake()->image() necesita la extensión GD, que no siempre está instalada.
    private function fotoPng(string $nombre): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ));
    }

    private function rutaDeUrl(string $url): string
    {
        return parse_url($url, PHP_URL_PATH);
    }

    public function test_foto_de_mi_perfil_se_guarda_y_se_puede_ver(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['rol' => 'Encargado']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'foto' => $this->fotoPng('yo.png'),
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->foto);
        Storage::disk('public')->assertExists($user->foto);

        $this->get($this->rutaDeUrl($user->fotoUrl()))->assertOk();
    }

    public function test_foto_de_otro_usuario_subida_por_el_dueno_se_puede_ver(): void
    {
        Storage::fake('public');
        $dueno = User::factory()->create(['rol' => 'Dueno']);
        $obrero = User::factory()->create(['rol' => 'Obrero']);

        $this->actingAs($dueno)->put(route('usuarios.update', $obrero), [
            'name' => $obrero->name,
            'email' => $obrero->email,
            'rol' => 'Obrero',
            'activo' => 1,
            'foto' => $this->fotoPng('obrero.png'),
        ])->assertSessionHasNoErrors();

        $obrero->refresh();
        $this->assertNotNull($obrero->foto);
        $this->get($this->rutaDeUrl($obrero->fotoUrl()))->assertOk();
    }

    public function test_por_storage_no_se_puede_salir_de_la_carpeta_ni_subir_sin_firma(): void
    {
        Storage::fake('public');

        $this->get('/storage/../.env')->assertNotFound();
        $this->call('PUT', '/storage/intruso.txt?upload=1', [], [], [], [], 'x')->assertForbidden();
        Storage::disk('public')->assertMissing('intruso.txt');
    }
}
