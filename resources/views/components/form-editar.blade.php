{{-- Formulario de editar que se muestra en la ventana flotante (parciales _editar, pedidos por fetch desde un
     enlace data-editar). `_editar_en` guarda la URL de editar: si el servidor lo devuelve con errores, el
     middleware ReabrirEdicionEnVentana hace que la ventana se vuelva a abrir con lo escrito. --}}
@props(['accion', 'titulo', 'archivos' => false, 'texto' => 'Guardar cambios'])

<form method="POST" action="{{ $accion }}" @if($archivos) enctype="multipart/form-data" @endif class="space-y-4" data-titulo-ventana="{{ $titulo }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="_editar_en" value="{{ request()->fullUrl() }}">
    {{ $slot }}

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
        <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
        <button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> {{ $texto }}</button>
    </div>
</form>
