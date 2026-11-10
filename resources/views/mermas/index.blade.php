@extends('layouts.app')

@section('title', 'Pérdidas de mercancía')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Pérdidas de mercancía" subtitulo="Producto dañado, vencido o extraviado. Lo que registres aquí se resta del stock.">
            <x-acciones-exportar :excel="route('mermas.excel')" :pdf="route('mermas.pdf')" />
        </x-encabezado>

        <!-- Resumen del mes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="tarjeta">
                <p class="cifra-etiqueta">Valor perdido este mes (a precio de venta)</p>
                <p class="cifra-valor text-rose-600 dark:text-rose-400">${{ number_format($valorPerdidoMes, 2) }}</p>
            </div>
            <div class="tarjeta">
                <p class="cifra-etiqueta">Unidades descartadas este mes</p>
                <p class="cifra-valor">{{ \App\Support\Cantidad::formato($cantidadMes) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulario de Registro -->
            <div class="tarjeta h-fit">
                <h3 class="titulo-tarjeta mb-4">Registrar pérdida
                </h3>

                <form action="{{ route('mermas.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="etiqueta">Producto</label>
                        <select name="producto_id" id="merma-producto" required onchange="ajustarCantidadSegunOpcion(this, document.getElementById('merma-cantidad'), document.getElementById('merma-ayuda-cantidad'))" class="campo">
                            <option value="">-- Seleccionar producto --</option>
                            @foreach($productos as $prod)
                                <option value="{{ $prod->id }}" data-entero="{{ $prod->usaCantidadEntera() ? 1 : 0 }}" data-ayuda="{{ $prod->ayudaCantidad() }}" @selected(old('producto_id', request('producto')) == $prod->id)>{{ $prod->nombre }} (Stock: {{ \App\Support\Cantidad::formato($prod->stock) }} {{ $prod->unidadLabel() }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="etiqueta">Cantidad a descartar</label>
                        <input type="number" name="cantidad" id="merma-cantidad" min="0.001" step="0.001" inputmode="decimal" data-entero="0" value="{{ old('cantidad') }}" required class="campo">
                        <p id="merma-ayuda-cantidad" class="ayuda-campo" data-sin-producto="{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}">{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}</p>
                    </div>

                    <div>
                        <label class="etiqueta">Motivo</label>
                        <select name="motivo" required class="campo">
                            @foreach(\App\Models\Merma::MOTIVOS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="etiqueta">Observación (Opcional)</label>
                        <textarea name="observacion" rows="2" class="campo"></textarea>
                    </div>

                    <button type="submit" class="btn-peligro w-full">
                        <x-icon name="trending-down" class="w-4 h-4" /> Registrar y descontar stock
                    </button>
                </form>
            </div>

            <!-- Historial -->
            <div class="tarjeta lg:col-span-2">
                <h3 class="titulo-tarjeta mb-4">Historial de pérdidas</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Fecha</th>
                                <th class="py-3 px-4 font-semibold">Producto</th>
                                <th class="py-3 px-4 font-semibold text-center">Cantidad</th>
                                <th class="py-3 px-4 font-semibold">Motivo</th>
                                <th class="py-3 px-4 font-semibold">Registrado por</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                            @forelse($mermas as $merma)
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                    <td class="py-3 px-4 text-xs">{{ $merma->fecha->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 px-4 font-bold text-stone-900 dark:text-stone-100">{{ $merma->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-3 px-4 text-center font-bold text-rose-600 dark:text-rose-400">-{{ \App\Support\Cantidad::formato($merma->cantidad) }}</td>
                                    <td class="py-3 px-4 text-xs">
                                        <span class="bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700 px-2.5 py-1 rounded-md font-semibold">
                                            {{ $merma->motivoLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs">{{ $merma->usuario->name ?? 'Sistema' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-stone-400 dark:text-stone-500">
                                        <x-icon name="leaf" class="w-8 h-8 mx-auto mb-2 text-stone-300 dark:text-stone-600" />
                                        No hay pérdidas registradas aún.
                                    </td>
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
        ajustarCantidadSegunOpcion(document.getElementById('merma-producto'), document.getElementById('merma-cantidad'), document.getElementById('merma-ayuda-cantidad'));
    </script>
@endpush
