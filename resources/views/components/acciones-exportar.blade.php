@props(['excel' => null, 'pdf' => null])

{{-- Grupo estándar de exportación: Excel, PDF (descarga) e Imprimir (abre el mismo PDF en otra pestaña para
     imprimirlo, nunca la pantalla). Se omite lo que no se pase. En el celular quedan solo los íconos. --}}
<div class="inline-flex items-center rounded-md border border-stone-300 dark:border-stone-600 bg-white dark:bg-stone-800 overflow-hidden divide-x divide-stone-300 dark:divide-stone-600 no-print">
    @if($excel)
        <a href="{{ $excel }}" class="flex items-center gap-1.5 px-3.5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700 transition" title="Exportar a Excel" aria-label="Exportar a Excel">
            <x-icon name="file-spreadsheet" class="w-4 h-4 text-emerald-700 dark:text-emerald-400" /> <span class="hidden sm:inline">Excel</span>
        </a>
    @endif
    @if($pdf)
        <a href="{{ $pdf }}" class="flex items-center gap-1.5 px-3.5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700 transition" title="Descargar PDF" aria-label="Descargar PDF">
            <x-icon name="download" class="w-4 h-4 text-rose-600 dark:text-rose-400" /> <span class="hidden sm:inline">PDF</span>
        </a>
        <a href="{{ $pdf . (str_contains($pdf, '?') ? '&' : '?') . 'ver=1' }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 px-3.5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700 transition" title="Abrir el PDF para imprimirlo" aria-label="Imprimir">
            <x-icon name="printer" class="w-4 h-4 text-stone-500 dark:text-stone-400" /> <span class="hidden sm:inline">Imprimir</span>
        </a>
    @endif
</div>
