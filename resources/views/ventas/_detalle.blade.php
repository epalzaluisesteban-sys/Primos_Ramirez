{{-- Detalle de una venta: contenido de la ventana flotante del listado (se pide por fetch) y de la página ventas.show.
     Variable: $venta con usuario, cliente, eliminadaPor y detalles.producto cargados. --}}
@php
    $gestiona = auth()->user()->esEncargadoOSuperior();
    $cobro = $venta->tipo_pago === 'credito' ? 'Fiado' : ($venta->moneda_pago === 'VES' ? 'Contado en bolívares' : 'Contado en dólares');
@endphp

<div class="space-y-4">
    @if($venta->anulada)
        <div class="aviso-rojo">
            <x-icon name="trash" class="w-4 h-4 mt-0.5 shrink-0" />
            <div>
                <p class="font-semibold">En la papelera desde el {{ optional($venta->anulada_at)->format('d/m/Y h:i A') }}@if($venta->eliminadaPor) · la eliminó {{ $venta->eliminadaPor->name }}@endif</p>
                @if($venta->motivo_anulacion)<p>Motivo: {{ $venta->motivo_anulacion }}</p>@endif
                <p class="text-xs mt-1">No cuenta en la caja, los reportes, el kardex ni el saldo del cliente.</p>
            </div>
        </div>
    @endif

    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
        <div>
            <dt class="cifra-etiqueta">Fecha</dt>
            <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $venta->fecha->format('d/m/Y h:i A') }}</dd>
        </div>
        <div>
            <dt class="cifra-etiqueta">Atendió</dt>
            <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $venta->usuario->name ?? 'Sistema' }}</dd>
        </div>
        <div>
            <dt class="cifra-etiqueta">Cliente</dt>
            <dd class="font-semibold text-stone-800 dark:text-stone-100">
                @if($venta->cliente && $gestiona)
                    <a href="{{ route('clientes.show', $venta->cliente) }}" class="text-emerald-700 dark:text-emerald-400 hover:underline">{{ $venta->cliente->nombre }}</a>
                @else
                    {{ $venta->cliente->nombre ?? 'Mostrador' }}
                @endif
            </dd>
        </div>
        <div>
            <dt class="cifra-etiqueta">Pago</dt>
            <dd>
                <span class="{{ $venta->tipo_pago === 'credito' ? 'insignia-naranja' : 'insignia-verde' }}">{{ $cobro }}</span>
            </dd>
        </div>
        <div>
            <dt class="cifra-etiqueta">Tasa BCV</dt>
            <dd class="font-semibold text-stone-800 dark:text-stone-100">
                {{ $venta->tasa_cambio ? 'Bs. ' . number_format($venta->tasa_cambio, 4, ',', '.') : 'Sin tasa' }}
            </dd>
        </div>
        <div>
            <dt class="cifra-etiqueta">Estado</dt>
            <dd>
                @if($venta->anulada)
                    <span class="insignia-roja">En la papelera</span>
                @elseif($venta->editada)
                    <span class="insignia-ambar">Editada</span>
                @else
                    <span class="insignia-gris">Normal</span>
                @endif
            </dd>
        </div>
    </dl>

    <div class="overflow-x-auto border border-stone-200 dark:border-stone-700 rounded-md">
        <table class="tabla">
            <thead>
                <tr>
                    <th class="py-2 px-3">Producto</th>
                    <th class="py-2 px-3 text-right">Cantidad</th>
                    <th class="py-2 px-3 text-right">Precio</th>
                    <th class="py-2 px-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                @foreach($venta->detalles as $det)
                    <tr>
                        <td class="py-2 px-3 text-stone-800 dark:text-stone-100">{{ $det->producto->nombre ?? 'Producto eliminado' }}</td>
                        <td class="py-2 px-3 text-right whitespace-nowrap">@cantidad($det->cantidad) {{ $det->producto?->unidadLabel() }}</td>
                        <td class="py-2 px-3 text-right whitespace-nowrap">@usd($det->precio_unitario)</td>
                        <td class="py-2 px-3 text-right whitespace-nowrap font-semibold text-stone-800 dark:text-stone-100">@usd($det->subtotal)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="caja-suave p-4 flex items-center justify-between gap-4">
        <span class="cifra-etiqueta">Total</span>
        <x-precio :usd="$venta->total" :ves="$venta->total_ves" class="items-end font-display text-xl text-emerald-800 dark:text-emerald-400" />
    </div>

    <div class="flex flex-wrap gap-2 no-print">
        <a href="{{ route('ventas.ticket', $venta) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icon name="printer" class="w-3.5 h-3.5" /> Ticket</a>
        <a href="{{ route('ventas.pdf', $venta) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icon name="download" class="w-3.5 h-3.5" /> PDF</a>

        @if($gestiona && $venta->puedeEditarse())
            <a href="{{ route('ventas.edit', $venta) }}" class="btn-secundario btn-sm"><x-icon name="edit" class="w-3.5 h-3.5" /> Editar</a>
        @endif
        @if($gestiona && $venta->puedeRestaurarse())
            <form action="{{ route('ventas.restaurar', $venta) }}" method="POST"
                  data-confirm="¿Restaurar la venta #{{ $venta->id }}? Se vuelve a descontar la mercancía del inventario y cuenta otra vez en la caja de hoy.">
                @csrf
                @method('PUT')
                <button type="submit" class="btn-primario btn-sm"><x-icon name="repetir" class="w-3.5 h-3.5" /> Restaurar venta</button>
            </form>
        @endif
    </div>

    @if($gestiona && $venta->puedeEditarse())
        <form action="{{ route('ventas.anular', $venta) }}" method="POST" class="pt-4 border-t border-stone-200 dark:border-stone-700 no-print"
              data-confirm="¿Mandar la venta #{{ $venta->id }} a la papelera? La mercancía vuelve al inventario y la venta deja de contar en la caja.">
            @csrf
            @method('PUT')
            <label class="etiqueta" for="motivo-{{ $venta->id }}">Motivo para eliminarla (Opcional)</label>
            <div class="flex flex-col sm:flex-row gap-2">
                <input id="motivo-{{ $venta->id }}" type="text" name="motivo_anulacion" maxlength="255" placeholder="Ej. se registró mal la cantidad" class="campo" autocomplete="off">
                <button type="submit" class="btn-peligro shrink-0"><x-icon name="trash" class="w-4 h-4" /> Eliminar venta</button>
            </div>
        </form>
    @elseif($gestiona && !$venta->anulada)
        <p class="ayuda-campo no-print">No se puede editar ni eliminar: {{ lcfirst($venta->motivoNoEditable()) }}</p>
    @endif
</div>
