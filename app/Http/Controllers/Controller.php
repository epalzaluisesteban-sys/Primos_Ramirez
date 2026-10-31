<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Controller
{
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
