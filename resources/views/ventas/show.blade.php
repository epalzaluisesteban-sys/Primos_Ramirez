@extends('layouts.app')

@section('title', 'Venta #' . $venta->id)

@section('content')
    <div class="pagina">
        <x-encabezado :titulo="'Venta #' . $venta->id" subtitulo="Lo que se vendió, a quién y cómo se cobró."
                      :volver="$venta->anulada ? route('ventas.papelera') : route('ventas.index')" />

        <div class="tarjeta">
            @include('ventas._detalle')
        </div>
    </div>
@endsection
