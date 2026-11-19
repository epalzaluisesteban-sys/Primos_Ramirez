@props(['categorias', 'valor' => null, 'id' => 'categoria_id', 'envoltura' => ''])

{{-- Filtro por categoría (frutas, verduras, hortalizas…) para formularios GET de listados y reportes.
     Los atributos sueltos (onchange…) van al <select>; "envoltura" son clases del contenedor (col-span…). --}}
<div @if($envoltura) class="{{ $envoltura }}" @endif>
    <label class="etiqueta" for="{{ $id }}">Categoría</label>
    <select id="{{ $id }}" name="categoria_id" {{ $attributes->merge(['class' => 'campo']) }}>
        <option value="">Todas</option>
        @foreach($categorias as $categoria)
            <option value="{{ $categoria->id }}" @selected((string) $valor === (string) $categoria->id)>{{ $categoria->nombre }}</option>
        @endforeach
    </select>
</div>
