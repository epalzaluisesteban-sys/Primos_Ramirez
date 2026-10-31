@extends('layouts.app')

@section('title', 'Historial de ' . $proveedor->nombre)

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-800 font-bold text-xl flex items-center justify-center border border-stone-300">
                    {{ strtoupper(substr($proveedor->nombre, 0, 1)) }}
                </span>
                <div>
                    <h2 class="font-display text-xl font-bold text-stone-900">{{ $proveedor->nombre }}</h2>
                    <p class="text-sm text-stone-500 mt-0.5">
                        {{ $proveedor->contacto ?: 'Sin persona de contacto' }}
                        @if($proveedor->telefono) · Tel: {{ $proveedor->telefono }} @endif
                        @if($proveedor->email) · {{ $proveedor->email }} @endif
                    </p>
                    @if($proveedor->direccion)
                        <p class="text-xs text-stone-400 mt-0.5">{{ $proveedor->direccion }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($proveedor->whatsappUrl())
                    <a href="{{ $proveedor->whatsappUrl() }}" target="_blank" rel="noopener" class="flex items-center gap-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                        <x-icon name="whatsapp" class="w-4 h-4" /> WhatsApp
                    </a>
                @endif
                <a href="{{ route('proveedores.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-stone-500 hover:text-stone-800">
                    <x-icon name="arrow-left" class="w-4 h-4" /> Volver a proveedores
                </a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 border-t-4 border-indigo-500">
                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Total Comprado (Histórico)</p>
                <p class="font-display text-2xl font-extrabold text-stone-800 mt-1">${{ number_format($totalComprado, 2) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 border-t-4 border-stone-400">
                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Última Compra</p>
                <p class="font-display text-2xl font-extrabold text-stone-800 mt-1">{{ $ultimaCompra ? $ultimaCompra->format('d/m/Y') : 'Sin compras' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 border-t-4 border-emerald-500">
                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Compras Registradas</p>
                <p class="font-display text-2xl font-extrabold text-stone-800 mt-1">{{ $compras->count() }}</p>
            </div>
        </div>

        <!-- Historial de compras -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
            <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                <x-icon name="truck" class="w-4 h-4 text-indigo-600" /> Historial de Compras
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600">
                    <thead class="text-stone-500 uppercase text-xs border-b border-stone-200">
                        <tr>
                            <th class="py-2 px-3 font-semibold">Fecha</th>
                            <th class="py-2 px-3 font-semibold">Producto</th>
                            <th class="py-2 px-3 font-semibold text-center">Cantidad</th>
                            <th class="py-2 px-3 font-semibold text-right">Costo U.</th>
                            <th class="py-2 px-3 font-semibold text-right">Total</th>
                            <th class="py-2 px-3 font-semibold">Registrado Por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse($compras as $compra)
                            <tr>
                                <td class="py-2.5 px-3 text-xs">{{ \Carbon\Carbon::parse($compra->fecha)->format('d/m/Y H:i') }}</td>
                                <td class="py-2.5 px-3 font-semibold text-stone-800">{{ $compra->producto->nombre ?? 'Eliminado' }}</td>
                                <td class="py-2.5 px-3 text-center">{{ $compra->cantidad }}</td>
                                <td class="py-2.5 px-3 text-right">${{ number_format($compra->precio_compra, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-indigo-700">${{ number_format($compra->total, 2) }}</td>
                                <td class="py-2.5 px-3 text-xs">{{ $compra->usuario->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-stone-400">Sin compras registradas a este proveedor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
