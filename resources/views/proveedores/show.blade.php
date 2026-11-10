@extends('layouts.app')

@section('title', 'Historial de ' . $proveedor->nombre)

@section('content')
    <div class="pagina">

        <x-encabezado :titulo="$proveedor->nombre" :volver="route('proveedores.index')">
            <x-slot:inicio>
                <span class="w-14 h-14 rounded-md bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-200 font-bold text-xl flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($proveedor->nombre, 0, 1)) }}
                </span>
            </x-slot:inicio>
            <x-slot:detalle>
                <p class="subtitulo-pagina">
                    @unless($proveedor->activo) <span class="insignia-gris mr-1">Inactivo</span> @endunless
                    {{ $proveedor->contacto ?: 'Sin persona de contacto' }}
                    @if($proveedor->telefono) · Tel: {{ \App\Support\Contacto::telefono($proveedor->telefono) }} @endif
                    @if($proveedor->email) · {{ $proveedor->email }} @endif
                    @if($proveedor->direccion) · {{ $proveedor->direccion }} @endif
                </p>
            </x-slot:detalle>
            @if($proveedor->whatsappUrl())
                <a href="{{ $proveedor->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secundario btn-sm text-emerald-700 dark:text-emerald-400">
                    <x-icon name="whatsapp" class="w-4 h-4" /> WhatsApp
                </a>
            @endif
            <a href="{{ route('proveedores.edit', $proveedor->id) }}" class="btn-secundario btn-sm">
                <x-icon name="edit" class="w-4 h-4" /> Editar
            </a>
        </x-encabezado>

        <!-- Resumen -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="tarjeta p-5">
                <p class="etiqueta mb-0">Total comprado</p>
                <x-precio :usd="$totalComprado" class="font-display text-2xl font-extrabold text-stone-800 dark:text-stone-100 mt-1" />
            </div>
            <div class="tarjeta p-5">
                <p class="etiqueta mb-0">Última compra</p>
                <p class="font-display text-2xl font-extrabold text-stone-800 dark:text-stone-100 mt-1">{{ $ultimaCompra ? $ultimaCompra->format('d/m/Y') : 'Sin compras' }}</p>
            </div>
            <div class="tarjeta p-5">
                <p class="etiqueta mb-0">Compras registradas</p>
                <p class="font-display text-2xl font-extrabold text-stone-800 dark:text-stone-100 mt-1">{{ $compras->count() }}</p>
            </div>
        </div>

        <!-- Historial de compras -->
        <div class="tarjeta p-0 overflow-hidden">
            <h3 class="titulo-tarjeta px-6 py-4 border-b border-stone-200 dark:border-stone-700">Historial de compras
            </h3>
            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Costo unit.</th>
                            <th class="text-right">Total</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compras as $compra)
                            <tr>
                                <td class="text-xs whitespace-nowrap">{{ \Carbon\Carbon::parse($compra->fecha)->format('d/m/Y H:i') }}</td>
                                <td class="font-semibold text-stone-800 dark:text-stone-100">{{ $compra->producto->nombre ?? 'Eliminado' }}</td>
                                <td class="text-center">{{ \App\Support\Cantidad::formato($compra->cantidad) }}</td>
                                <td class="text-right">@usd($compra->precio_compra)</td>
                                <td class="text-right font-bold text-indigo-700 dark:text-indigo-300">@usd($compra->total)</td>
                                <td class="text-xs">{{ $compra->usuario->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="tabla-vacia">Todavía no se le ha comprado nada a este proveedor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
