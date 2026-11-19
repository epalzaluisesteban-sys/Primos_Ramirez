@extends('layouts.app')

@section('title', 'Recibir mercancía')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Recibir mercancía" subtitulo="Anota lo que llega del proveedor. Suma al stock y actualiza el costo de cada producto.">
            <x-acciones-exportar :excel="route('compras.excel')" :pdf="route('compras.pdf')" />
            <button type="button" data-abrir-ventana="ventana-compra" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Registrar entrada</button>
        </x-encabezado>

        <div>
            <div class="tarjeta p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">
                    <h2 class="titulo-tarjeta">Historial de entradas</h2>
                    <span class="insignia-gris">{{ $compras->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="hidden md:table-cell text-right">Costo U.</th>
                                <th class="text-right">Total</th>
                                <th class="hidden lg:table-cell">Proveedor</th>
                                <th class="hidden sm:table-cell text-center">Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($compras as $compra)
                                <tr>
                                    <td class="text-xs whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($compra->fecha)->format('d/m/Y') }}</td>
                                    <td>
                                        <p class="font-bold text-stone-900 dark:text-stone-100">{{ $compra->producto->nombre ?? 'Producto eliminado' }}</p>
                                        <p class="lg:hidden text-[11px] text-stone-500 dark:text-stone-400">{{ $compra->nombreProveedor() }}{{ $compra->tipo_pago === 'credito' ? ' · a crédito' : '' }}</p>
                                    </td>
                                    <td class="text-right font-bold whitespace-nowrap text-emerald-800 dark:text-emerald-400">+@cantidad($compra->cantidad)</td>
                                    <td class="hidden md:table-cell text-right"><x-precio :usd="$compra->precio_compra" size="small" class="items-end" /></td>
                                    <td class="text-right font-bold text-stone-800 dark:text-stone-100"><x-precio :usd="$compra->total" size="small" class="items-end" /></td>
                                    <td class="hidden lg:table-cell text-xs">{{ $compra->nombreProveedor() }}</td>
                                    <td class="hidden sm:table-cell text-center">
                                        @if($compra->tipo_pago === 'credito')
                                            <span class="insignia-naranja" title="{{ $compra->vence_el ? 'Vence el ' . $compra->vence_el->format('d/m/Y') : 'Sin fecha de vencimiento' }}">Crédito</span>
                                        @else
                                            <span class="insignia-gris">Contado</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="tabla-vacia">Todavía no hay entradas de mercancía. <button type="button" data-abrir-ventana="ventana-compra" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Registra la primera</button>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- ?producto=ID llega desde el botón "Comprar" de Productos: abre la ventana con ese producto elegido --}}
    <x-ventana id="ventana-compra" titulo="Registrar entrada de mercancía" subtitulo="Suma al stock y recalcula el costo promedio del producto." :abierta="request()->filled('producto')">
        <form action="{{ route('compras.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_ventana" value="ventana-compra">
            <div>
                <label class="etiqueta" for="compra-producto">Producto</label>
                <select name="producto_id" id="compra-producto" required onchange="ajustarCantidadSegunOpcion(this, document.getElementById('compra-cantidad'), document.getElementById('compra-ayuda-cantidad'))" class="campo @error('producto_id') border-rose-500 @enderror">
                    <option value="">Selecciona…</option>
                    @foreach($productos as $prod)
                        {{-- ?producto=ID llega desde el botón "Comprar" de Productos para dejarlo preseleccionado --}}
                        <option value="{{ $prod->id }}" data-entero="{{ $prod->usaCantidadEntera() ? 1 : 0 }}" data-ayuda="{{ $prod->ayudaCantidad() }}" @selected(old('producto_id', request('producto')) == $prod->id)>
                            {{ $prod->nombre }} (Stock actual: {{ \App\Support\Cantidad::formato($prod->stock) }} {{ $prod->unidadLabel() }})
                        </option>
                    @endforeach
                </select>
                @error('producto_id') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="etiqueta" for="compra-cantidad">Cantidad recibida</label>
                <input type="number" name="cantidad" id="compra-cantidad" min="0.001" max="9999999" step="0.001" inputmode="decimal" data-entero="0" value="{{ old('cantidad') }}" required class="campo @error('cantidad') border-rose-500 @enderror">
                <p id="compra-ayuda-cantidad" class="ayuda-campo" data-sin-producto="{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}">{{ \App\Models\Producto::AYUDA_SIN_PRODUCTO }}</p>
                @error('cantidad') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="etiqueta" for="compra-precio">Costo unitario de compra ($)</label>
                <input type="number" step="0.01" min="0.01" max="99999999" inputmode="decimal" id="compra-precio" name="precio_compra" value="{{ old('precio_compra') }}" required class="campo @error('precio_compra') border-rose-500 @enderror" placeholder="0.00">
                @error('precio_compra') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="etiqueta" for="compra-proveedor">Proveedor</label>
                <select name="proveedor_id" id="compra-proveedor" onchange="mostrarCreditoProveedor()" class="campo @error('proveedor_id') border-rose-500 @enderror">
                    <option value="" data-credito="">Sin proveedor registrado</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}" @selected(old('proveedor_id') == $prov->id)
                                data-credito="{{ $prov->da_credito ? ($prov->dias_credito ? "Da crédito a {$prov->dias_credito} días." : 'Da crédito (sin plazo fijo).') : '' }}">
                            {{ $prov->nombre }}{{ $prov->categorias->isNotEmpty() ? ' — ' . $prov->categorias->pluck('nombre')->join(', ') : '' }}
                        </option>
                    @endforeach
                </select>
                @error('proveedor_id') <p class="error-campo">{{ $message }}</p> @enderror
                <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">
                    ¿No está en la lista? <a href="{{ route('proveedores.create') }}" class="text-emerald-800 dark:text-emerald-400 font-semibold hover:underline">Registrar nuevo proveedor</a>
                </p>
            </div>

            <div>
                <label class="etiqueta" for="compra-proveedor-texto">O escribe el proveedor (si no está registrado)</label>
                <input type="text" id="compra-proveedor-texto" name="proveedor" value="{{ old('proveedor') }}" maxlength="255" placeholder="Ej. Distribuidora Central" class="campo">
            </div>

            <fieldset>
                <legend class="etiqueta">Forma de pago</legend>
                <div class="grid grid-cols-2 gap-2">
                    <label class="caja-suave flex items-center gap-2 p-3 cursor-pointer text-sm font-semibold text-stone-700 dark:text-stone-200">
                        <input type="radio" name="tipo_pago" value="contado" @checked(old('tipo_pago', 'contado') === 'contado') class="casilla"> De contado
                    </label>
                    <label class="caja-suave flex items-center gap-2 p-3 cursor-pointer text-sm font-semibold text-stone-700 dark:text-stone-200">
                        <input type="radio" name="tipo_pago" value="credito" @checked(old('tipo_pago') === 'credito') class="casilla"> A crédito
                    </label>
                </div>
                <p id="compra-ayuda-credito" class="ayuda-campo">A crédito solo con un proveedor registrado que dé crédito. Queda como deuda en su ficha.</p>
                @error('tipo_pago') <p class="error-campo">{{ $message }}</p> @enderror
            </fieldset>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
                <button type="submit" class="btn-primario"><x-icon name="trending-up" class="w-4 h-4" /> Registrar y sumar al stock</button>
            </div>
        </form>
    </x-ventana>
@endsection

@push('scripts')
    <script>
        // Producto preseleccionado (desde alertas o tras un error): cantidad entera si se cuenta por unidad
        ajustarCantidadSegunOpcion(document.getElementById('compra-producto'), document.getElementById('compra-cantidad'), document.getElementById('compra-ayuda-cantidad'));

        // Debajo de "Forma de pago" se dice si el proveedor elegido da crédito y a cuántos días
        function mostrarCreditoProveedor() {
            const opcion = document.getElementById('compra-proveedor').selectedOptions[0];
            const ayuda = document.getElementById('compra-ayuda-credito');
            ayuda.textContent = !opcion.value
                ? 'A crédito solo con un proveedor registrado que dé crédito. Queda como deuda en su ficha.'
                : (opcion.dataset.credito || 'Este proveedor no tiene marcado que da crédito: regístrala de contado.');
        }
        mostrarCreditoProveedor();
    </script>
@endpush
