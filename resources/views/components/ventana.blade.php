{{-- Ventana flotante para formularios ("Nuevo cliente", "Registrar pérdida"…) y fichas de detalle.
     Uso: <button type="button" data-abrir-ventana="ventana-cliente" class="btn-primario">Nuevo cliente</button>
          <x-ventana id="ventana-cliente" titulo="Nuevo cliente" subtitulo="…"> <form>…</form> </x-ventana>
     Los formularios dentro llevan <input type="hidden" name="_ventana" value="{id}">: si el servidor los devuelve
     con errores, la ventana se vuelve a abrir sola con lo escrito. :abierta="true" la abre al cargar la página.
     En el celular sale desde abajo, a todo el ancho. El script está en layouts/app.blade.php. --}}
@props(['id', 'titulo' => '', 'subtitulo' => null, 'ancho' => 'sm:max-w-lg', 'abierta' => false])

@php $abierta = $abierta || old('_ventana') === $id; @endphp
<div id="{{ $id }}" data-ventana @if($abierta) data-abrir-al-cargar @endif
     class="hidden fixed inset-0 z-[190] flex items-end sm:items-center justify-center sm:p-4 no-print"
     role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-titulo">
    <div class="absolute inset-0 bg-stone-900/50 dark:bg-black/60" data-cerrar-ventana></div>
    <div class="relative w-full {{ $ancho }} max-h-[92dvh] overflow-y-auto bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-t-md sm:rounded-md shadow-xl p-4 sm:p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div class="min-w-0">
                <h2 id="{{ $id }}-titulo" class="titulo-tarjeta text-lg" data-ventana-titulo>{{ $titulo }}</h2>
                @if($subtitulo)
                    <p class="ayuda-campo">{{ $subtitulo }}</p>
                @endif
            </div>
            <button type="button" data-cerrar-ventana class="btn-icono-gris shrink-0" title="Cerrar" aria-label="Cerrar"><x-icon name="x" class="w-4 h-4" /></button>
        </div>
        <div data-ventana-contenido>{{ $slot }}</div>
    </div>
</div>
