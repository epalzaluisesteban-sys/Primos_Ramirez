<x-form-editar :accion="route('productos.update', $producto)" titulo="Editar producto" archivos>
    @include('productos._form')
</x-form-editar>
