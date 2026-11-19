@extends('layouts.app')

@section('title', $proveedor->nombre)

@section('content')
    @php $sinTasa = \App\Support\Moneda::tasa() <= 0; @endphp
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
                    @if($proveedor->rif) · RIF: {{ $proveedor->rif }} @endif
                    @if($proveedor->telefono) · Tel: {{ \App\Support\Contacto::telefono($proveedor->telefono) }} @endif
                    @if($proveedor->email) · {{ $proveedor->email }} @endif
                    @if($proveedor->direccion) · {{ $proveedor->direccion }} @endif
                </p>
                <p class="subtitulo-pagina mt-1">
                    Distribuye: {{ $proveedor->categorias->pluck('nombre')->join(', ') ?: 'sin categorías marcadas' }}
                    · {{ $proveedor->da_credito ? 'Da crédito' . ($proveedor->dias_credito ? " a {$proveedor->dias_credito} días" : '') : 'No da crédito' }}
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

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Le debemos</p>
                <x-precio :usd="$deuda['saldo']" class="cifra-valor {{ $deuda['saldo'] > 0 ? 'text-orange-700 dark:text-orange-400' : '' }}" />
                @if($deuda['saldo'] > 0 && $deuda['vence_el'])
                    <p class="text-xs mt-1 {{ $deuda['vencido'] ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-stone-500 dark:text-stone-400' }}">
                        {{ $deuda['vencido'] ? 'Vencido desde el' : 'Vence el' }} {{ $deuda['vence_el']->format('d/m/Y') }}
                    </p>
                @endif
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Total comprado</p>
                <x-precio :usd="$totalComprado" class="cifra-valor" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Última compra</p>
                <p class="cifra-valor">{{ $ultimaCompra ? $ultimaCompra->format('d/m/Y') : 'Sin compras' }}</p>
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Compras registradas</p>
                <p class="cifra-valor">{{ $compras->count() }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="tarjeta h-fit">
                <h3 class="titulo-tarjeta mb-4">Registrar pago</h3>

                @if($deuda['saldo'] <= 0)
                    <p class="caja-suave p-4 text-sm text-center text-stone-500 dark:text-stone-400">No se le debe nada a este proveedor.</p>
                @else
                    <form action="{{ route('proveedores.pagos.store', $proveedor) }}" method="POST" class="space-y-4"
                          data-confirm="¿Registrar el pago a {{ $proveedor->nombre }}? Baja lo que se le debe y no se puede borrar.">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="etiqueta" for="pago-monto">Monto</label>
                                <input type="number" id="pago-monto" name="monto" value="{{ old('monto') }}" required step="0.01" min="0.01" max="9999999999"
                                       inputmode="decimal" placeholder="0.00" class="campo @error('monto') border-rose-500 @enderror">
                            </div>
                            <div>
                                <label class="etiqueta" for="pago-moneda">Moneda</label>
                                <select id="pago-moneda" name="moneda" required class="campo @error('moneda') border-rose-500 @enderror">
                                    <option value="USD" @selected(old('moneda', 'USD') === 'USD')>Dólares ($)</option>
                                    <option value="VES" @selected(old('moneda') === 'VES') @disabled($sinTasa)>Bolívares (Bs.)</option>
                                </select>
                            </div>
                        </div>
                        <p class="ayuda-campo -mt-2">Máximo: @usd($deuda['saldo']) / @bs($deuda['saldo']) (en Bs. se convierte con la tasa BCV de hoy).</p>
                        @error('monto') <p class="error-campo">{{ $message }}</p> @enderror

                        <fieldset>
                            <legend class="etiqueta">¿De dónde salió el dinero?</legend>
                            <div class="space-y-2">
                                <label class="caja-suave flex items-start gap-2 p-3 {{ $cajaAbierta ? 'cursor-pointer' : 'opacity-60' }}">
                                    <input type="radio" name="origen" value="caja" @checked(old('origen') === 'caja') @disabled(!$cajaAbierta) class="casilla mt-0.5">
                                    <span class="text-sm text-stone-700 dark:text-stone-200">
                                        <span class="font-semibold">Efectivo de la caja</span>
                                        <span class="block text-[11px] text-stone-500 dark:text-stone-400">{{ $cajaAbierta ? 'Se resta del efectivo esperado al cerrar la caja de hoy.' : 'La caja de hoy no está abierta.' }}</span>
                                    </span>
                                </label>
                                <label class="caja-suave flex items-start gap-2 p-3 cursor-pointer">
                                    <input type="radio" name="origen" value="otro" @checked(old('origen', 'otro') === 'otro') class="casilla mt-0.5">
                                    <span class="text-sm text-stone-700 dark:text-stone-200">
                                        <span class="font-semibold">Otra cuenta</span>
                                        <span class="block text-[11px] text-stone-500 dark:text-stone-400">Transferencia, pago móvil o dinero fuera de la caja.</span>
                                    </span>
                                </label>
                            </div>
                            @error('origen') <p class="error-campo">{{ $message }}</p> @enderror
                        </fieldset>

                        <div>
                            <label class="etiqueta" for="pago-notas">Notas (Opcional)</label>
                            <textarea id="pago-notas" name="notas" rows="2" maxlength="500" placeholder="Ej. Referencia 00123" class="campo @error('notas') border-rose-500 @enderror">{{ old('notas') }}</textarea>
                            @error('notas') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-primario w-full">
                            <x-icon name="check" class="w-4 h-4" /> Registrar pago
                        </button>
                    </form>
                @endif
            </div>

            <div class="tarjeta p-0 overflow-hidden lg:col-span-2">
                <h3 class="titulo-tarjeta px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">Pagos hechos</h3>
                <div class="overflow-x-auto">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>De dónde</th>
                                <th>Notas</th>
                                <th>Pagó</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pagos as $pago)
                                <tr>
                                    <td class="text-xs whitespace-nowrap">{{ $pago->fecha->format('d/m/Y h:i A') }}</td>
                                    <td><span class="{{ $pago->salioDeCaja() ? 'insignia-verde' : 'insignia-gris' }}">{{ $pago->salioDeCaja() ? 'Caja' : 'Otra cuenta' }}</span></td>
                                    <td class="text-xs">{{ $pago->notas ?: '—' }}</td>
                                    <td class="text-xs">{{ $pago->usuario->name ?? 'Sistema' }}</td>
                                    <td class="text-right whitespace-nowrap font-bold">
                                        {{ $pago->moneda === 'VES' ? \App\Support\Moneda::ves($pago->monto_moneda) : \App\Support\Moneda::usd($pago->monto_moneda) }}
                                        @if($pago->moneda === 'VES') <span class="block text-[11px] font-normal text-stone-500 dark:text-stone-400">@usd($pago->monto)</span> @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="tabla-vacia">Todavía no se le ha pagado nada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tarjeta p-0 overflow-hidden">
            <h3 class="titulo-tarjeta px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">Historial de compras</h3>
            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Costo unit.</th>
                            <th class="text-right">Total</th>
                            <th class="text-center">Pago</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compras as $compra)
                            <tr>
                                <td class="text-xs whitespace-nowrap">{{ \Carbon\Carbon::parse($compra->fecha)->format('d/m/Y h:i A') }}</td>
                                <td class="font-semibold text-stone-800 dark:text-stone-100">{{ $compra->producto->nombre ?? 'Eliminado' }}</td>
                                <td class="text-center">{{ \App\Support\Cantidad::formato($compra->cantidad) }}</td>
                                <td class="text-right">@usd($compra->precio_compra)</td>
                                <td class="text-right font-bold text-stone-800 dark:text-stone-100">@usd($compra->total)</td>
                                <td class="text-center whitespace-nowrap">
                                    @if($compra->tipo_pago === 'credito')
                                        <span class="insignia-naranja">Crédito</span>
                                        @if($compra->vence_el) <span class="block text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">vence {{ $compra->vence_el->format('d/m/Y') }}</span> @endif
                                    @else
                                        <span class="insignia-gris">Contado</span>
                                    @endif
                                </td>
                                <td class="text-xs">{{ $compra->usuario->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="tabla-vacia">Todavía no se le ha comprado nada a este proveedor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
