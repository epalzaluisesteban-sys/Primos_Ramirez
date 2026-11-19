<?php

namespace App\Support;

use App\Models\Producto;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\Exceptions\BarcodeException;

/**
 * Código de barras (Code 128) y QR de la etiqueta de un producto, como SVG generado en el servidor.
 * Sirve igual en la pantalla del kardex y en el PDF de la etiqueta (dompdf dibuja SVG), sin depender
 * de JavaScript ni de la impresión del navegador.
 */
class Etiqueta
{
    /** Texto del QR: el código QR del producto o, si no tiene, "PROD-{id}". */
    public static function textoQr(Producto $producto): string
    {
        return $producto->codigo_qr ?: 'PROD-' . $producto->id;
    }

    // Code 128 solo codifica ASCII imprimible (sin tildes ni ñ): lo demás saldría ilegible para el lector
    public const REGLA_CODIGO_BARRAS = 'regex:/^[\x21-\x7E]+$/';

    /** SVG del código de barras, o null si el producto no tiene o tiene caracteres que Code 128 no admite. */
    public static function codigoBarrasSvg(Producto $producto): ?string
    {
        if (!$producto->codigo_barras || !preg_match('/^[\x21-\x7E]+$/', $producto->codigo_barras)) {
            return null;
        }

        try {
            $svg = (new BarcodeGeneratorSVG())->getBarcode($producto->codigo_barras, BarcodeGeneratorSVG::TYPE_CODE_128, 2, 50);
        } catch (BarcodeException) {
            return null;
        }

        // Sin la cabecera XML/DOCTYPE, para poder ponerlo dentro de la página
        return substr($svg, strpos($svg, '<svg'));
    }

    public static function qrSvg(Producto $producto): string
    {
        $opciones = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
        ]);

        return (new QRCode($opciones))->render(self::textoQr($producto));
    }

    /** Para usar el SVG en un <img> (dompdf lo dibuja así). */
    public static function comoImagen(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
