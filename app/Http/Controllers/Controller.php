<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\PDF;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Controller
{
    /**
     * Entrega un PDF: con ?ver=1 lo abre en el navegador para imprimirlo (botón "Imprimir");
     * sin él, lo descarga (botón "PDF"). Así imprimir nunca es una captura de la pantalla.
     */
    protected function entregarPdf(PDF $pdf, string $nombreArchivo): Response
    {
        return request()->boolean('ver') ? $pdf->stream($nombreArchivo) : $pdf->download($nombreArchivo);
    }

    /**
     * Ticket de 80 mm en PDF (layout pdf.ticket), abierto en el navegador para mandarlo a la impresora.
     * El papel de ticket no tiene alto fijo: se calcula por las líneas que lleva, así no sobra papel en blanco.
     */
    protected function entregarTicket(string $vista, array $datos, int $lineas, string $nombreArchivo): Response
    {
        $ancho = 80 / 25.4 * 72;           // 80 mm en puntos
        $alto = 30 + $lineas * 9;           // márgenes + ~9 pt por línea (Courier de 10–11 px)

        return \Barryvdh\DomPDF\Facade\Pdf::loadView($vista, $datos)
            ->setPaper([0, 0, $ancho, $alto])
            ->stream($nombreArchivo);
    }

    /**
     * Descarga un CSV compatible con Excel (UTF-8 con BOM).
     *
     * $escribir recibe un callable $fila(array $columnas) que escribe cada fila.
     * El nombre de archivo lo define quien llama.
     */
    protected function descargarCsv(string $nombreArchivo, callable $escribir): StreamedResponse
    {
        return response()->streamDownload(function () use ($escribir) {
            $handle = fopen('php://output', 'w');
            // BOM para que Excel detecte UTF-8 correctamente
            fwrite($handle, "\xEF\xBB\xBF");

            $escribir(fn (array $columnas) => fputcsv($handle, $columnas));

            fclose($handle);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
