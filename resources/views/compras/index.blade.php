@extends('layouts.app')

@section('title', 'Entrada de Inventario')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Reabastecimiento de Inventario</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Registra entradas de mercancía y consulta el historial de compras.</p>
            </div>
            <x-acciones-exportar :excel="route('compras.excel')" :pdf="route('compras.pdf')" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulario de Entrada de Mercancía -->
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 h-fit">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4 flex items-center gap-2">
                    <x-icon name="inbox-down" class="w-4 h-4 text-emerald-600" /> Registrar Entrada de Producto
                </h3>

                <form action="{{ route('compras.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Producto</label>
                        <select name="producto_id" required class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- Seleccionar Producto --</option>
                            @foreach($productos as $prod)
                                <option value="{{ $prod->id }}">
                                    {{ $prod->nombre }} (Stock actual: {{ $prod->stock }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Cantidad Recibida</label>
                        <input type="number" name="cantidad" min="0.001" step="0.001" required class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Costo Unitario de Compra ($)</label>
                        <input type="number" step="0.01" name="precio_compra" required class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500" placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Proveedor</label>
                        <select name="proveedor_id" class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
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
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">O escribe el proveedor (si no está registrado)</label>
                        <input type="text" name="proveedor" placeholder="Ej. Distribuidora Central" class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm border border-stone-200 dark:border-stone-700 transition text-sm">
                        <x-icon name="trending-up" class="w-4 h-4" /> Aumentar Stock
                    </button>
                </form>
            </div>

            <!-- Tabla de Historial de Compras -->
            <div class="lg:col-span-2 bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4">Historial de Entradas de Mercancía</h3>

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
                                    <td class="py-3 px-4 text-center font-bold text-emerald-800 dark:text-emerald-400">+{{ $compra->cantidad }}</td>
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
