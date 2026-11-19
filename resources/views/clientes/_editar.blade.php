<x-form-editar :accion="route('clientes.update', $cliente)" titulo="Editar cliente" archivos>
    @include('clientes._form')
</x-form-editar>
