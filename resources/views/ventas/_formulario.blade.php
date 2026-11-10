{{-- Formulario compartido por "Nueva venta" y "Editar venta".
     Variables: $productos, $clientes, $venta (null al crear), $accion, $textoBoton. --}}
@php
    $esEdicion = isset($venta) && $venta;
    // Si la venta rebotó (sin stock, límite de crédito…), se vuelven a mostrar los productos que se habían cargado
    $lineasPrevias = old('productos');
    if (is_array($lineasPrevias) && count($lineasPrevias) > 0) {
        $lineas = collect($lineasPrevias)->values()->map(fn ($l) => [
            'producto_id' => $l['id'] ?? null,
            'cantidad' => $l['cantidad'] ?? 1,
        ]);
    } elseif ($esEdicion) {
        $lineas = $venta->detalles->map(fn ($d) => ['producto_id' => $d->producto_id, 'cantidad' => $d->cantidad]);
    } else {
        $lineas = collect([null]);
    }
    // Al editar, el stock mostrado incluye lo que esta venta devolvería al inventario
    $stockExtra = $esEdicion ? $venta->detalles->groupBy('producto_id')->map->sum('cantidad')->all() : [];
    $esCredito = old('tipo_pago', $esEdicion ? $venta->tipo_pago : 'contado') === 'credito';
    $clienteActual = old('cliente_id', $esEdicion ? $venta->cliente_id : null);
    $monedaPago = old('moneda_pago', $esEdicion ? $venta->moneda_pago : 'USD');
    $tasaVigente = \App\Support\Moneda::tasa();
@endphp

<form action="{{ $accion }}" method="POST" id="form-venta" data-tasa="{{ $tasaVigente }}">
    @csrf
    @if($esEdicion) @method('PUT') @endif

    <div id="items-container" class="space-y-3 mb-6">
        @foreach($lineas as $i => $linea)
            @include('ventas._fila', ['i' => $i, 'linea' => $linea, 'productos' => $productos, 'stockExtra' => $stockExtra])
        @endforeach
    </div>

    <template id="plantilla-fila">
        @include('ventas._fila', ['i' => '__I__', 'linea' => null, 'productos' => $productos, 'stockExtra' => $stockExtra])
    </template>

    <div class="mb-6 pb-6 border-b border-stone-200 dark:border-stone-700">
        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase mb-2">Forma de pago</label>
        <div class="flex gap-3">
            <label class="flex-1 flex items-center justify-center gap-2 border-2 border-emerald-200 dark:border-emerald-800 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md py-3 cursor-pointer transition">
                <input type="radio" name="tipo_pago" value="contado" @checked(!$esCredito) class="accent-emerald-600">
                <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Contado</span>
            </label>
            <label class="flex-1 flex items-center justify-center gap-2 border-2 border-orange-200 dark:border-orange-800 has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-900/20 rounded-md py-3 cursor-pointer transition">
                <input type="radio" name="tipo_pago" value="credito" @checked($esCredito) class="accent-orange-600">
                <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Fiado / crédito</span>
            </label>
        </div>

        <div id="moneda-wrapper" class="{{ $esCredito ? 'hidden' : '' }} mt-4">
            <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase mb-2">El cliente paga en</label>
            <div class="flex gap-3">
                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md py-2.5 cursor-pointer transition">
                    <input type="radio" name="moneda_pago" value="USD" @checked($monedaPago !== 'VES') class="accent-emerald-600">
                    <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Dólares ($)</span>
                </label>
                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md py-2.5 cursor-pointer transition @if($tasaVigente <= 0) opacity-50 @endif">
                    <input type="radio" name="moneda_pago" value="VES" @checked($monedaPago === 'VES') @disabled($tasaVigente <= 0) class="accent-emerald-600">
                    <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Bolívares (Bs.)</span>
                </label>
            </div>
            <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-1.5">
                @if($tasaVigente > 0) Tasa BCV: Bs. {{ number_format($tasaVigente, 4, ',', '.') }} por $1. @else No hay tasa BCV cargada: solo se puede cobrar en dólares. @endif
            </p>
        </div>

        <div id="cliente-wrapper" class="{{ $esCredito ? '' : 'hidden' }} mt-4">
            <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase mb-1.5">Cliente</label>
            <select name="cliente_id" id="cliente_id" class="campo">
                <option value="">-- Seleccionar cliente --</option>
                @foreach($clientes as $cli)
                    @php
                        $deuda = $cli->saldoPendiente();
                        // Al editar una venta a crédito de este cliente, su monto actual se libera (igual que en verificarLimiteCredito)
                        $liberado = ($esEdicion && $venta->tipo_pago === 'credito' && (int) $venta->cliente_id === $cli->id) ? (float) $venta->total : 0;
                        $disponible = $cli->creditoDisponible($deuda - $liberado);
                    @endphp
                    <option value="{{ $cli->id }}" @selected((int) $clienteActual === $cli->id)
                            data-deuda="{{ $deuda }}" data-disponible="{{ $disponible ?? '' }}">
                        {{ $cli->nombre }}@if($deuda > 0) (Debe: ${{ number_format($deuda, 2) }})@endif
                        @if($disponible !== null) — Disponible: ${{ number_format($disponible, 2) }}@endif
                    </option>
                @endforeach
            </select>

            {{-- Crédito del cliente elegido; se actualiza con el total de la venta --}}
            <div id="credito-info" class="hidden mt-2 caja-suave px-3 py-2.5 text-sm">
                <div class="flex flex-wrap justify-between gap-x-4 gap-y-1">
                    <span class="text-stone-600 dark:text-stone-300">Debe: <strong id="credito-deuda">$0.00</strong></span>
                    <span class="text-stone-600 dark:text-stone-300">Crédito disponible: <strong id="credito-disponible" class="text-emerald-700 dark:text-emerald-400">$0.00</strong></span>
                </div>
                <div id="credito-aviso" class="hidden mt-2">
                    <p class="flex items-start gap-1.5 text-xs font-semibold text-rose-700 dark:text-rose-400">
                        <x-icon name="alert-triangle" class="w-4 h-4 shrink-0" />
                        <span>Esta venta supera el crédito disponible. Quita productos, cobra de contado o pide un abono primero.</span>
                    </p>
                </div>
            </div>
            <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">
                ¿Cliente nuevo? <a href="{{ route('clientes.create') }}" class="text-orange-700 dark:text-orange-400 font-semibold hover:underline">Regístralo primero</a> y vuelve a esta pantalla.
            </p>
        </div>
    </div>

    <div class="flex justify-between items-center mb-6 pt-4 border-t border-stone-200 dark:border-stone-700">
        <button type="button" id="btn-agregar-fila" class="flex items-center gap-1.5 bg-stone-100 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600 text-stone-600 font-semibold py-2 px-4 rounded-md text-sm transition">
            <x-icon name="plus" class="w-4 h-4" /> Agregar producto
        </button>
        <div class="text-right">
            <span class="text-xs text-stone-500 dark:text-stone-400 uppercase font-semibold block">Total general</span>
            <span class="font-display text-2xl font-extrabold text-emerald-800 dark:text-emerald-400">$<span id="total-general">{{ number_format($venta->total ?? 0, 2) }}</span></span>
            <span class="block text-sm font-bold text-stone-600 dark:text-stone-300" id="total-ves-wrapper">Bs. <span id="total-ves">{{ number_format(\App\Support\Moneda::aVes($venta->total ?? 0), 2, ',', '.') }}</span></span>
        </div>
    </div>

    <button type="submit" class="btn-primario w-full py-3.5 text-base">
        <x-icon name="check" class="w-5 h-5" /> {{ $textoBoton }}
    </button>
</form>

@push('scripts')
    <script>
        (function () {
            const contenedor = document.getElementById('items-container');
            const plantilla = document.getElementById('plantilla-fila').innerHTML;
            let indiceFila = contenedor.querySelectorAll('.item-row').length;

            function calcularTotales() {
                let total = 0;
                contenedor.querySelectorAll('.item-row').forEach(function (fila) {
                    const select = fila.querySelector('.producto-select');
                    const precio = parseFloat(select.options[select.selectedIndex].dataset.precio) || 0;
                    const cantidad = parseFloat(fila.querySelector('.cantidad-input').value) || 0;
                    const subtotal = Math.round(precio * cantidad * 100) / 100;
                    fila.querySelector('.subtotal-span').textContent = subtotal.toFixed(2);
                    total += subtotal;
                });
                document.getElementById('total-general').textContent = total.toFixed(2);
                const tasa = parseFloat(document.getElementById('form-venta').dataset.tasa) || 0;
                document.getElementById('total-ves-wrapper').classList.toggle('hidden', tasa <= 0);
                document.getElementById('total-ves').textContent = (total * tasa).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                mostrarCredito(total);
            }

            // Muestra lo que debe y el crédito disponible del cliente elegido, y avisa si la venta lo supera.
            // El servidor también lo revisa (verificarLimiteCredito): esto solo avisa antes de enviar.
            function mostrarCredito(total) {
                const select = document.getElementById('cliente_id');
                const opcion = select.options[select.selectedIndex];
                const info = document.getElementById('credito-info');
                if (!opcion || !opcion.value) {
                    info.classList.add('hidden');
                    return;
                }
                info.classList.remove('hidden');
                document.getElementById('credito-deuda').textContent = '$' + (parseFloat(opcion.dataset.deuda) || 0).toFixed(2);

                const sinLimite = opcion.dataset.disponible === '';
                const disponible = parseFloat(opcion.dataset.disponible) || 0;
                const campo = document.getElementById('credito-disponible');
                campo.textContent = sinLimite ? 'Sin límite' : '$' + disponible.toFixed(2);
                const supera = !sinLimite && total > disponible + 0.001;
                campo.classList.toggle('text-emerald-700', !supera);
                campo.classList.toggle('dark:text-emerald-400', !supera);
                campo.classList.toggle('text-rose-700', supera);
                campo.classList.toggle('dark:text-rose-400', supera);
                document.getElementById('credito-aviso').classList.toggle('hidden', !supera);
            }

            function alternarCliente() {
                const credito = document.querySelector('input[name="tipo_pago"]:checked').value === 'credito';
                document.getElementById('cliente-wrapper').classList.toggle('hidden', !credito);
                document.getElementById('moneda-wrapper').classList.toggle('hidden', credito);
                document.getElementById('cliente_id').required = credito;
            }

            document.getElementById('btn-agregar-fila').addEventListener('click', function () {
                contenedor.insertAdjacentHTML('beforeend', plantilla.replaceAll('__I__', indiceFila++));
                calcularTotales();
            });

            contenedor.addEventListener('click', function (e) {
                const boton = e.target.closest('[data-quitar-fila]');
                if (!boton) return;
                // Siempre debe quedar al menos un producto
                if (contenedor.querySelectorAll('.item-row').length <= 1) return;
                boton.closest('.item-row').remove();
                calcularTotales();
            });

            contenedor.addEventListener('input', calcularTotales);
            contenedor.addEventListener('change', function (e) {
                // Al elegir un producto por unidad, la cantidad pasa a ser sin decimales
                if (e.target.classList.contains('producto-select')) {
                    ajustarCantidadSegunOpcion(e.target, e.target.closest('.item-row').querySelector('.cantidad-input'));
                }
                calcularTotales();
            });
            document.querySelectorAll('input[name="tipo_pago"]').forEach(function (r) { r.addEventListener('change', alternarCliente); });
            document.getElementById('cliente_id').addEventListener('change', calcularTotales);

            alternarCliente();
            calcularTotales();
        })();
    </script>
@endpush
