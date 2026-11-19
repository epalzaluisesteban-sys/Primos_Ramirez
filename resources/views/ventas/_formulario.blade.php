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

<form action="{{ $accion }}" method="POST" id="form-venta" data-tasa="{{ $tasaVigente }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    @csrf
    @if($esEdicion) @method('PUT') @endif

    {{-- Columna 1: lo que se lleva el cliente. "Agregar otro producto" va justo debajo de la última línea. --}}
    <section class="tarjeta lg:col-span-2" aria-labelledby="titulo-productos">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 id="titulo-productos" class="titulo-tarjeta">Productos</h2>
            <span id="cuenta-productos" class="insignia-gris"></span>
        </div>

        <div id="items-container">
            @foreach($lineas as $i => $linea)
                @include('ventas._fila', ['i' => $i, 'linea' => $linea, 'productos' => $productos, 'stockExtra' => $stockExtra])
            @endforeach
        </div>

        <button type="button" id="btn-agregar-fila" class="btn-secundario w-full mt-4 border-dashed">
            <x-icon name="plus" class="w-4 h-4" /> Agregar otro producto
        </button>
    </section>

    <template id="plantilla-fila">
        @include('ventas._fila', ['i' => '__I__', 'linea' => null, 'productos' => $productos, 'stockExtra' => $stockExtra])
    </template>

    {{-- Columna 2: cómo paga, a quién y cuánto. En escritorio queda fija al bajar por la lista. --}}
    <section class="tarjeta space-y-5 lg:sticky lg:top-20" aria-labelledby="titulo-cobro">
        <h2 id="titulo-cobro" class="titulo-tarjeta">Cobro</h2>

        <fieldset>
            <legend class="etiqueta">Forma de pago</legend>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md min-h-[2.625rem] px-2 cursor-pointer transition">
                    <input type="radio" name="tipo_pago" value="contado" @checked(!$esCredito) class="casilla">
                    <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Contado</span>
                </label>
                <label class="flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-900/20 rounded-md min-h-[2.625rem] px-2 cursor-pointer transition">
                    <input type="radio" name="tipo_pago" value="credito" @checked($esCredito) class="casilla accent-orange-600">
                    <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Fiado</span>
                </label>
            </div>
        </fieldset>

        <fieldset id="moneda-wrapper" class="{{ $esCredito ? 'hidden' : '' }}">
            <legend class="etiqueta">El cliente paga en</legend>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md min-h-[2.625rem] px-2 cursor-pointer transition">
                    <input type="radio" name="moneda_pago" value="USD" @checked($monedaPago !== 'VES') class="casilla">
                    <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Dólares</span>
                </label>
                <label class="flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md min-h-[2.625rem] px-2 cursor-pointer transition @if($tasaVigente <= 0) opacity-50 @endif">
                    <input type="radio" name="moneda_pago" value="VES" @checked($monedaPago === 'VES') @disabled($tasaVigente <= 0) class="casilla">
                    <span class="text-sm font-semibold text-stone-700 dark:text-stone-300">Bolívares</span>
                </label>
            </div>
            <p class="ayuda-campo">
                @if($tasaVigente > 0) Tasa BCV: Bs. {{ number_format($tasaVigente, 4, ',', '.') }} por $1. @else No hay tasa BCV cargada: solo se puede cobrar en dólares. @endif
            </p>
        </fieldset>

        <div id="cliente-wrapper">
            <label for="cliente_id" class="etiqueta">
                Cliente <span id="cliente-opcional" class="normal-case font-normal">(Opcional)</span>
            </label>
            <select name="cliente_id" id="cliente_id" class="campo">
                <option value="">Sin cliente (mostrador)</option>
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
            <p id="cliente-ayuda" class="ayuda-campo"></p>
            <button type="button" id="btn-cliente-nuevo" class="btn-secundario btn-sm mt-2">
                <x-icon name="plus" class="w-4 h-4" /> Registrar cliente nuevo
            </button>

            {{-- Crédito del cliente elegido; se actualiza con el total de la venta --}}
            <div id="credito-info" class="hidden mt-3 caja-suave px-3 py-2.5 text-sm space-y-1">
                <p class="flex justify-between gap-3 text-stone-600 dark:text-stone-300">Debe <strong id="credito-deuda">$0.00</strong></p>
                <p class="flex justify-between gap-3 text-stone-600 dark:text-stone-300">Crédito disponible <strong id="credito-disponible" class="text-emerald-700 dark:text-emerald-400">$0.00</strong></p>
                <p id="credito-aviso" class="hidden pt-1 items-start gap-1.5 text-xs font-semibold text-rose-700 dark:text-rose-400">
                    <x-icon name="alert-triangle" class="w-4 h-4 shrink-0" />
                    <span>Esta venta supera el crédito disponible. Quita productos, cobra de contado o pide un abono primero.</span>
                </p>
            </div>
        </div>

        <div class="border-t border-stone-200 dark:border-stone-700 pt-4">
            <p class="cifra-etiqueta">Total a cobrar</p>
            <p class="font-display text-3xl font-extrabold text-emerald-800 dark:text-emerald-400">$<span id="total-general">{{ number_format($venta->total ?? 0, 2) }}</span></p>
            <p class="text-sm font-bold text-stone-600 dark:text-stone-300" id="total-ves-wrapper">Bs. <span id="total-ves">{{ number_format(\App\Support\Moneda::aVes($venta->total ?? 0), 2, ',', '.') }}</span></p>
        </div>

        <button type="submit" class="btn-primario w-full">
            <x-icon name="check" class="w-5 h-5" /> {{ $textoBoton }}
        </button>
    </section>
</form>

{{-- Ventana para registrar un cliente sin salir de la venta. Va fuera del formulario de la venta
     (no se pueden anidar formularios) y se envía por fetch a clientes.rapido. --}}
<div id="modal-cliente" class="hidden fixed inset-0 z-[190] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true" aria-labelledby="modal-cliente-titulo">
    <div class="absolute inset-0 bg-stone-900/50 dark:bg-black/60" data-cerrar-cliente></div>
    <div class="tarjeta relative shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 id="modal-cliente-titulo" class="titulo-tarjeta">Cliente nuevo</h2>
                <p class="ayuda-campo">Queda elegido en esta venta. El límite de crédito lo pone un encargado en Clientes.</p>
            </div>
            <button type="button" data-cerrar-cliente class="btn-icono-gris" title="Cerrar" aria-label="Cerrar"><x-icon name="x" class="w-4 h-4" /></button>
        </div>

        <form id="form-cliente-rapido" action="{{ route('clientes.rapido') }}" method="POST" class="space-y-4" novalidate>
            @csrf
            <div>
                <label class="etiqueta" for="rapido-nombre">Nombre del cliente</label>
                <input id="rapido-nombre" type="text" name="nombre" required maxlength="255" placeholder="Ej. María González" class="campo" autocomplete="off">
                <p data-error="nombre" class="error-campo hidden"></p>
            </div>

            <div>
                <label class="etiqueta" for="rapido-telefono">Teléfono (Opcional)</label>
                <input id="rapido-telefono" type="tel" name="telefono" inputmode="numeric" maxlength="11" pattern="0[0-9]{10}"
                       oninput="soloDigitos(this, 11, true)" placeholder="04121234567" class="campo" autocomplete="off">
                <p class="ayuda-campo">Solo números, 11 dígitos (ej. 04121234567).</p>
                <p data-error="telefono" class="error-campo hidden"></p>
            </div>

            <div>
                <label class="etiqueta" for="rapido-documento">Cédula o RIF (Opcional)</label>
                <div class="flex gap-2">
                    <select name="documento_tipo" aria-label="Tipo de documento" class="campo w-20 shrink-0">
                        @foreach(\App\Support\Contacto::TIPOS_DOCUMENTO as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                    <input id="rapido-documento" type="text" name="documento_numero" inputmode="numeric" maxlength="9"
                           oninput="soloDigitos(this, 9)" placeholder="12345678" class="campo" autocomplete="off">
                </div>
                <p data-error="documento_tipo" class="error-campo hidden"></p>
                <p data-error="documento_numero" class="error-campo hidden"></p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <button type="button" data-cerrar-cliente class="btn-secundario">Cancelar</button>
                <button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> Guardar cliente</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const contenedor = document.getElementById('items-container');
            const plantilla = document.getElementById('plantilla-fila').innerHTML;
            let indiceFila = contenedor.querySelectorAll('.item-row').length;

            function calcularTotales() {
                let total = 0;
                const filas = contenedor.querySelectorAll('.item-row');
                document.getElementById('cuenta-productos').textContent = filas.length + (filas.length === 1 ? ' línea' : ' líneas');
                filas.forEach(function (fila) {
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
                if (!opcion || !opcion.value || !esCredito()) {
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
                const aviso = document.getElementById('credito-aviso');
                aviso.classList.toggle('hidden', !supera);
                aviso.classList.toggle('flex', supera);
            }

            function esCredito() {
                return document.querySelector('input[name="tipo_pago"]:checked').value === 'credito';
            }

            // En fiado el cliente es obligatorio; en contado es opcional y solo deja la venta en su ficha.
            function alternarCliente() {
                const credito = esCredito();
                document.getElementById('moneda-wrapper').classList.toggle('hidden', credito);
                document.getElementById('cliente_id').required = credito;
                document.getElementById('cliente-opcional').classList.toggle('hidden', credito);
                document.getElementById('cliente_id').options[0].textContent = credito ? 'Selecciona el cliente…' : 'Sin cliente (mostrador)';
                document.getElementById('cliente-ayuda').textContent = credito
                    ? 'A quién se le fía. Lo que debe aparece en su ficha de Clientes.'
                    : 'Si lo eliges, la compra queda en su ficha de Clientes.';
                calcularTotales();
            }

            // ---- Cliente nuevo sin salir de la venta ----
            const modalCliente = document.getElementById('modal-cliente');
            const formCliente = document.getElementById('form-cliente-rapido');

            function abrirModalCliente() {
                modalCliente.classList.remove('hidden');
                document.getElementById('rapido-nombre').focus();
            }

            function cerrarModalCliente() {
                modalCliente.classList.add('hidden');
                document.getElementById('btn-cliente-nuevo').focus();
            }

            function mostrarErroresCliente(errores) {
                formCliente.querySelectorAll('[data-error]').forEach(function (p) {
                    const mensaje = (errores[p.dataset.error] || [])[0];
                    p.textContent = mensaje || '';
                    p.classList.toggle('hidden', !mensaje);
                });
            }

            document.getElementById('btn-cliente-nuevo').addEventListener('click', abrirModalCliente);
            modalCliente.querySelectorAll('[data-cerrar-cliente]').forEach(function (el) { el.addEventListener('click', cerrarModalCliente); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modalCliente.classList.contains('hidden')) cerrarModalCliente();
            });

            formCliente.addEventListener('submit', async function (e) {
                // preventDefault antes que el loader del layout: así no deshabilita el botón ni deja la barra cargando
                e.preventDefault();
                const boton = formCliente.querySelector('button[type="submit"]');
                boton.disabled = true;

                try {
                    const respuesta = await fetch(formCliente.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(formCliente),
                    });
                    const datos = await respuesta.json();

                    if (respuesta.status === 422) {
                        mostrarErroresCliente(datos.errors || {});
                        return;
                    }
                    if (!respuesta.ok) {
                        throw new Error();
                    }

                    const select = document.getElementById('cliente_id');
                    const opcion = new Option(datos.nombre, datos.id, true, true);
                    opcion.dataset.deuda = '0';
                    opcion.dataset.disponible = '';
                    select.add(opcion);
                    calcularTotales();

                    formCliente.reset();
                    mostrarErroresCliente({});
                    cerrarModalCliente();
                    mostrarToast('Cliente ' + datos.nombre + ' registrado y elegido en esta venta.', 'success');
                } catch (error) {
                    mostrarToast('No se pudo guardar el cliente. Revisa la conexión e inténtalo de nuevo.', 'error');
                } finally {
                    boton.disabled = false;
                }
            });

            document.getElementById('btn-agregar-fila').addEventListener('click', function () {
                contenedor.insertAdjacentHTML('beforeend', plantilla.replaceAll('__I__', indiceFila++));
                calcularTotales();
                contenedor.lastElementChild.querySelector('.producto-select').focus();
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
