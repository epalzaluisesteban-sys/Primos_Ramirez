@extends('layouts.app')

@section('title', 'Pérdidas de mercancía')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Pérdidas de mercancía" subtitulo="Producto dañado, vencido o extraviado. Lo que registres aquí se resta del stock. Toca una para ver su detalle.">
            <x-acciones-exportar :excel="route('mermas.excel')" :pdf="route('mermas.pdf')" />
            <button type="button" data-abrir-ventana="ventana-merma" class="btn-peligro"><x-icon name="plus" class="w-4 h-4" /> Registrar pérdida</button>
        </x-encabezado>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Perdido este mes</p>
                <p class="cifra-valor text-rose-600 dark:text-rose-400">@usd($valorPerdidoMes)</p>
                <p class="text-[11px] text-stone-500 dark:text-stone-400">A precio de venta</p>
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Descartado este mes</p>
                <p class="cifra-valor">@cantidad($cantidadMes)</p>
                <p class="text-[11px] text-stone-500 dark:text-stone-400">Unidades, kg o litros</p>
            </div>
        </div>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="flex items-center gap-2 px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">
                <h2 class="titulo-tarjeta">Historial de pérdidas</h2>
                <span class="insignia-gris">{{ $mermas->count() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th class="text-right">Cantidad</th>
                            <th class="hidden sm:table-cell">Motivo</th>
                            <th class="hidden md:table-cell">Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mermas as $merma)
                            <tr data-ficha="{{ route('mermas.show', $merma) }}" data-titulo="Pérdida registrada" class="cursor-pointer">
                                <td class="text-xs whitespace-nowrap">{{ $merma->fecha->format('d/m/Y') }}<span class="block text-stone-500 dark:text-stone-400">{{ $merma->fecha->format('h:i A') }}</span></td>
                                <td>
                                    <a href="{{ route('mermas.show', $merma) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:underline" aria-label="Ver detalle de la pérdida de {{ $merma->producto->nombre ?? 'producto eliminado' }}">{{ $merma->producto->nombre ?? 'Producto eliminado' }}</a>
                                    <p class="sm:hidden text-[11px] text-stone-500 dark:text-stone-400">{{ $merma->motivoLabel() }}</p>
                                    @if($merma->observacion)
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ $merma->observacion }}</p>
                                    @endif
                                </td>
                                <td class="text-right font-bold whitespace-nowrap text-rose-600 dark:text-rose-400">−@cantidad($merma->cantidad) <span class="font-normal text-xs text-stone-500 dark:text-stone-400">{{ $merma->producto?->unidadLabel() }}</span></td>
                                <td class="hidden sm:table-cell"><span class="insignia-roja">{{ $merma->motivoLabel() }}</span></td>
                                <td class="hidden md:table-cell text-xs">{{ $merma->usuario->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="tabla-vacia">
                                    Todavía no hay pérdidas registradas.
                                    <button type="button" data-abrir-ventana="ventana-merma" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Registrar una</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ?producto=ID llega desde Productos (producto vencido): abre la ventana con ese producto elegido --}}
    <x-ventana id="ventana-merma" titulo="Registrar pérdida" subtitulo="La cantidad se resta del stock del producto." :abierta="request()->filled('producto')">
        <form action="{{ route('mermas.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_ventana" value="ventana-merma">
            <div>
                <label class="etiqueta" for="merma-producto">Producto</label>
                <select name="producto_id" id="merma-producto" required onchange="ajustarCantidadSegunOpcion(this, document.getElementById('merma-cantidad'), document.getElementById('merma-ayuda-cantidad'))" class="campo @error('producto_id') border-rose-500 @enderror">
                    <option value="">Selecciona…</option>
                    @foreach($productos as $prod)
                        <option value="{{ $prod->id }}" data-entero="{{ $prod->usaCantidadEntera() ? 1 : 0 }}" data-ayuda="{{ $prod->ayudaCantidad() }}" @selected(old('producto_id', request('producto')) == $prod->id)>{{ $prod->nombre }} (Stock: {{ \App\Support\Cantidad::formato($prod->stock) }} {{ $prod->unidadLabel() }})</option>
                    @endforeach
                </select>
                @error('producto_id') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="etiqueta" for="merma-cantidad">Cantidad</label>
                    <input type="number" name="cantidad" id="merma-cantidad" min="0.001" max="999999" step="0.001" inputmode="decimal" data-entero="0" value="{{ old('cantidad') }}" required class="campo @error('cantidad') border-rose-500 @enderror">
                    @error('cantidad') <p class="error-campo">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="etiqueta" for="merma-motivo">Motivo</label>
                    <select id="merma-motivo" name="motivo" required class="campo @error('motivo') border-rose-500 @enderror">
                        @foreach(\App\Models\Merma::MOTIVOS as $key => $label)
                            <option value="{{ $key }}" @selected(old('motivo') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('motivo') <p class="error-campo">{{ $message }}</p> @enderror
                </div>
            </div>
            <p id="merma-ayuda-cantidad" class="ayuda-campo -mt-2" data-sin-producto="{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}">{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}</p>

            <div>
                <label class="etiqueta" for="merma-observacion">Observación (obligatoria si el motivo es "Otro")</label>
                <textarea id="merma-observacion" name="observacion" rows="2" maxlength="500" placeholder="Ej. Se cayó la caja al descargar" class="campo @error('observacion') border-rose-500 @enderror">{{ old('observacion') }}</textarea>
                @error('observacion') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
                <button type="submit" class="btn-peligro"><x-icon name="trending-down" class="w-4 h-4" /> Registrar y descontar stock</button>
            </div>
        </form>
    </x-ventana>
@endsection

@push('scripts')
    <script>
        // Producto preseleccionado (desde Productos o tras un error): cantidad entera si se cuenta por unidad
        ajustarCantidadSegunOpcion(document.getElementById('merma-producto'), document.getElementById('merma-cantidad'), document.getElementById('merma-ayuda-cantidad'));
    </script>
@endpush
