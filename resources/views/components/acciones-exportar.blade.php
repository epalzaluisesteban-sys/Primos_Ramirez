@props(['excel' => null, 'pdf' => null, 'imprimir' => true])

{{-- Grupo estándar de exportación: Excel, PDF e Imprimir. Se omite lo que no se pase. --}}
<div class="inline-flex items-center rounded-xl border border-stone-300 dark:border-stone-600 bg-white dark:bg-stone-800 shadow-sm overflow-hidden divide-x divide-stone-300 dark:divide-stone-600 no-print">
    @if($excel)
        <a href="{{ $excel }}" class="flex items-center gap-1.5 px-3.5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700 transition" title="Exportar a Excel">
            <x-icon name="file-spreadsheet" class="w-4 h-4 text-emerald-700 dark:text-emerald-400" /> Excel
        </a>
    @endif
    @if($pdf)
        <a href="{{ $pdf }}" target="_blank" class="flex items-center gap-1.5 px-3.5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700 transition" title="Descargar PDF">
            <x-icon name="download" class="w-4 h-4 text-rose-600 dark:text-rose-400" /> PDF
        </a>
    @endif
    @if($imprimir)
        <button type="button" onclick="window.print()" class="flex items-center gap-1.5 px-3.5 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700 transition" title="Imprimir esta página">
            <x-icon name="printer" class="w-4 h-4 text-stone-500 dark:text-stone-400" /> Imprimir
        </button>
    @endif
</div>
