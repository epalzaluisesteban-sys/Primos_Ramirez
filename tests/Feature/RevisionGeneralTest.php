<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guardas que revisan todas las vistas, para que lo que pidieron los tutores no se rompa con cambios futuros:
 * imprimir siempre es un PDF y cada campo de formulario tiene su nombre en español para los mensajes de error.
 */
class RevisionGeneralTest extends TestCase
{
    /** @return array<string, string> ruta relativa => contenido, sin la guía "Cómo usar" (es de práctica) */
    private function vistas(): array
    {
        $vistas = [];
        $base = resource_path('views');
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base)) as $archivo) {
            $ruta = str_replace('\\', '/', substr($archivo->getPathname(), strlen($base) + 1));
            if (str_ends_with($ruta, '.blade.php') && !str_starts_with($ruta, 'ayuda/')) {
                $vistas[$ruta] = file_get_contents($archivo->getPathname());
            }
        }

        return $vistas;
    }

    public function test_ninguna_pantalla_se_imprime_con_window_print(): void
    {
        $conImpresion = array_keys(array_filter($this->vistas(), fn ($html) => str_contains($html, 'window.print')));

        $this->assertSame([], $conImpresion, 'Imprimir debe abrir un PDF (Controller::entregarPdf), no imprimir la pantalla.');
    }

    public function test_cada_campo_de_formulario_tiene_su_nombre_en_espanol(): void
    {
        $atributos = trans('validation.attributes', [], 'es');
        $sinNombre = [];

        foreach ($this->vistas() as $ruta => $html) {
            preg_match_all('/<(?:input|select|textarea)\b[^>]*\bname="([a-z_]+)/', $html, $campos);
            foreach (array_unique($campos[1]) as $campo) {
                if (!in_array($campo, ['_token', '_method', '_ventana'], true) && !isset($atributos[$campo])) {
                    $sinNombre[] = "{$campo} ({$ruta})";
                }
            }
        }

        $this->assertSame([], $sinNombre, 'Agrega estos campos a lang/es/validation.php → attributes.');
    }
}
