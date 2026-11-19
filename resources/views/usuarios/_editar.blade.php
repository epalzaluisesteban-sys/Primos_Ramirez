<x-form-editar :accion="route('usuarios.update', $usuario)" titulo="Editar usuario" archivos>
    @include('usuarios._campos')
</x-form-editar>
