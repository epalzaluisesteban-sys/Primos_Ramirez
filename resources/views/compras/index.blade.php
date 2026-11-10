@extends('layouts.app')

@section('title', 'Recibir mercancía')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Recibir mercancía" subtitulo="Anota lo que llega del proveedor. Suma al stock y actualiza el costo de cada producto.">
            <x-acciones-exportar :excel="route('compras.excel')" :pdf="route('compras.pdf')" />
        </x-encabezado>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulario de Entrada de Mercancía -->
            <div class="tarjeta h-fit">
                <h3 class="titulo-tarjeta mb-4">Registrar entrada de producto
                </h3>

                <form action="{{ route('compras.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="etiqueta">Producto</label>
                        <select name="producto_id" id="compra-producto" required onchange="ajustarCantidadSegunOpcion(this, document.getElementById('compra-cantidad'), document.getElementById('compra-ayuda-cantidad'))" class="campo">
                            <option value="">-- Seleccionar producto --</option>
                            @foreach($productos as $prod)
                                {{-- ?producto=ID llega desde el botón "Comprar" de Productos para dejarlo preseleccionado --}}
                                <option value="{{ $prod->id }}" data-entero="{{ $prod->usaCantidadEntera() ? 1 : 0 }}" data-ayuda="{{ $prod->ayudaCantidad() }}" @selected(old('producto_id', request('producto')) == $prod->id)>
                                    {{ $prod->nombre }} (Stock actual: {{ \App\Support\Cantidad::formato($prod->stock) }} {{ $prod->unidadLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="etiqueta">Cantidad recibida</label>
                        <input type="number" name="cantidad" id="compra-cantidad" min="0.001" step="0.001" inputmode="decimal" data-entero="0" value="{{ old('cantidad') }}" required class="campo">
                        <p id="compra-ayuda-cantidad" class="ayuda-campo" data-sin-producto="{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}">{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}</p>
                    </div>

                    <div>
                        <label class="etiqueta">Costo unitario de compra ($)</label>
                        <input type="number" step="0.01" name="precio_compra" required class="campo" placeholder="0.00">
                    </div>

                    <div>
                        <label class="etiqueta">Proveedor</label>
                        <select name="proveedor_id" class="campo">
                            <option value="">-- Sin proveedor registrado --</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">
                            ¿No está en la lista? <a href="{{ route('proveedores.create') }}" class="text-emerald-800 dark:text-emerald-400 font-semibold hover:underline">Registrar nuevo proveedor</a>
                        </p>
                    </div>

                    <div>
                        <label class="etiqueta">O escribe el proveedor (si no está registrado)</label>
                        <input type="text" name="proveedor" placeholder="Ej. Distribuidora Central" class="campo">
                    </div>

                    <button type="submit" class="btn-primario w-full">
                        <x-icon name="trending-up" class="w-4 h-4" /> Aumentar stock
                    </button>
                </form>
            </div>

            <!-- Tabla de Historial de Compras -->
            <div class="tarjeta lg:col-span-2">
                <h3 class="titulo-tarjeta mb-4">Historial de entradas de mercancía</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Fecha</th>
                                <th class="py-3 px-4 font-semibold">Producto</th>
                                <th class="py-3 px-4 font-semibold text-center">Cantidad</th>
                                <th class="py-3 px-4 font-semibold text-right">Costo U.</th>
                                <th class="py-3 px-4 font-semibold text-right">Total</th>
                                <th class="py-3 px-4 font-semibold">Proveedor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                            @forelse($compras as $compra)
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                    <td class="py-3 px-4 text-xs">{{ $compra->fecha }}</td>
                                    <td class="py-3 px-4 font-bold text-stone-900 dark:text-stone-100">{{ $compra->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-3 px-4 text-center font-bold text-emerald-800 dark:text-emerald-400">+{{ \App\Support\Cantidad::formato($compra->cantidad) }}</td>
                                    <td class="py-3 px-4 text-right"><x-precio :usd="$compra->precio_compra" size="small" class="items-end" /></td>
                                    <td class="py-3 px-4 text-right font-bold text-stone-800 dark:text-stone-100"><x-precio :usd="$compra->total" size="small" class="items-end" /></td>
                                    <td class="py-3 px-4 text-xs">{{ $compra->nombreProveedor() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-stone-500 dark:text-stone-400">No hay compras o entradas registradas aún.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // Producto preseleccionado (desde alertas o tras un error): cantidad entera si se cuenta por unidad
        ajustarCantidadSegunOpcion(document.getElementById('compra-producto'), document.getElementById('compra-cantidad'), document.getElementById('compra-ayuda-cantidad'));
    </script>
@endpush
