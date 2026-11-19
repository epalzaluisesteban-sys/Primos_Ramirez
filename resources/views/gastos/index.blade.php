@extends('layouts.app')

@section('title', 'Gastos')

@section('content')
    @php
        $gestiona = auth()->user()->esEncargadoOSuperior();
        $sinTasa = \App\Support\Moneda::tasa() <= 0;
        $filtros = array_filter(request()->only(['fecha_inicio', 'fecha_fin', 'tipo']));
        $hoy = now()->toDateString();
    @endphp
    <div class="pagina">

        <x-encabezado titulo="Gastos" subtitulo="Lo que se paga con el efectivo de la caja (fletes, comida, servicios). Se resta del efectivo al cerrar la caja.">
            @if($gestiona)
                <x-acciones-exportar :excel="route('gastos.excel', $filtros)" :pdf="route('gastos.pdf', $filtros)" />
            @endif
            @if($puedeAnotar)
                <button type="button" data-abrir-ventana="ventana-gasto" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Anotar gasto</button>
            @else
                <a href="{{ route('caja.index') }}" class="btn-secundario"><x-icon name="banknote" class="w-4 h-4" /> Caja del día</a>
            @endif
        </x-encabezado>

        @unless($puedeAnotar)
            <div class="aviso-ambar mb-4 sm:mb-6">
                <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" />
                <span>
                    @if($cajaPendiente)
                        La caja del {{ $cajaPendiente->fechaCorta() }} sigue abierta. Ciérrala para abrir la de hoy y poder anotar gastos.
                    @elseif($cajaHoy && $cajaHoy->estado !== 'ABIERTA')
                        La caja de hoy ya está cerrada. Si falta anotar un gasto, pide a un encargado que la reabra.
                    @else
                        Los gastos salen del efectivo de la caja: ábrela primero para anotarlos.
                    @endif
                    <a href="{{ route('caja.index') }}" class="font-semibold underline">Ir a la caja</a>
                </span>
            </div>
        @endunless

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Pagado en $</p>
                <p class="cifra-valor text-rose-700 dark:text-rose-400">@usd($totalUsd)</p>
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Pagado en Bs.</p>
                <p class="cifra-valor text-rose-700 dark:text-rose-400">@ves($totalVes)</p>
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Total equivalente</p>
                <p class="cifra-valor">@usd($totalEquivalente)</p>
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Gastos anotados</p>
                <p class="cifra-valor">{{ $gastos->count() }}</p>
            </div>
        </div>

        <x-filtros id="filtros-gastos" :activos="count($filtros)" class="tarjeta mb-4 sm:mb-6">
            <form action="{{ route('gastos.index') }}" method="GET" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 items-end">
                <div>
                    <label class="etiqueta" for="fecha_inicio">Desde</label>
                    <input id="fecha_inicio" type="date" name="fecha_inicio" value="{{ $desde->toDateString() }}" max="{{ $hoy }}" class="campo">
                </div>
                <div>
                    <label class="etiqueta" for="fecha_fin">Hasta</label>
                    <input id="fecha_fin" type="date" name="fecha_fin" value="{{ $hasta->toDateString() }}" max="{{ $hoy }}" class="campo @error('fecha_fin') border-rose-500 @enderror">
                    @error('fecha_fin') <p class="error-campo">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2 lg:col-span-1">
                    <label class="etiqueta" for="filtro-tipo">Tipo</label>
                    <select id="filtro-tipo" name="tipo" class="campo">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Gasto::CATEGORIAS as $valor => $nombre)
                            <option value="{{ $valor }}" @selected($tipo === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 lg:col-span-1 flex gap-2">
                    <button type="submit" class="btn-oscuro flex-1"><x-icon name="search" class="w-4 h-4" /> Filtrar</button>
                    <a href="{{ route('gastos.index', ['fecha_inicio' => $hoy, 'fecha_fin' => $hoy]) }}" class="btn-secundario" title="Solo los de hoy">Hoy</a>
                    @if($filtros)
                        <a href="{{ route('gastos.index') }}" class="btn-secundario px-3" title="Volver al mes en curso" aria-label="Quitar filtro"><x-icon name="x" class="w-4 h-4" /></a>
                    @endif
                </div>
            </form>
        </x-filtros>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 items-start">
            <section class="tarjeta p-0 overflow-hidden lg:col-span-2" aria-labelledby="titulo-registro">
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">
                    <h2 id="titulo-registro" class="titulo-tarjeta">Registro de gastos</h2>
                    <span class="insignia-gris">{{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th class="hidden sm:table-cell">Tipo</th>
                                <th class="text-right">Monto</th>
                                @if($gestiona)
                                    <th class="text-right"><span class="sr-only">Acciones</span></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gastos as $gasto)
                                <tr data-ficha="{{ route('gastos.show', $gasto) }}" data-titulo="Gasto" class="cursor-pointer">
                                    <td class="text-xs whitespace-nowrap">
                                        {{ $gasto->fecha->format('d/m/Y') }}
                                        <span class="block text-stone-500 dark:text-stone-400">{{ $gasto->fecha->format('h:i A') }}<span class="hidden md:inline"> · {{ $gasto->usuario->name ?? 'Sistema' }}</span></span>
                                    </td>
                                    <td class="text-stone-800 dark:text-stone-100">
                                        <a href="{{ route('gastos.show', $gasto) }}" class="hover:underline" aria-label="Ver detalle del gasto {{ $gasto->concepto }}">{{ $gasto->concepto }}</a>
                                        <span class="sm:hidden block text-[11px] text-stone-500 dark:text-stone-400">{{ $gasto->nombreCategoria() }}</span>
                                    </td>
                                    <td class="hidden sm:table-cell"><span class="insignia-gris">{{ $gasto->nombreCategoria() }}</span></td>
                                    <td class="text-right whitespace-nowrap">
                                        <span class="block font-bold text-rose-700 dark:text-rose-400">{{ $gasto->moneda === 'VES' ? \App\Support\Moneda::ves($gasto->monto_moneda) : \App\Support\Moneda::usd($gasto->monto_moneda) }}</span>
                                        @if($gasto->moneda === 'VES')
                                            <span class="block text-[11px] text-stone-500 dark:text-stone-400">≈ @usd($gasto->monto)</span>
                                        @endif
                                    </td>
                                    @if($gestiona)
                                        <td class="text-right cursor-default" data-acciones>
                                            @if($gasto->caja && $gasto->caja->estado === 'ABIERTA' && $gasto->caja->esDeHoy())
                                                <form action="{{ route('gastos.destroy', $gasto) }}" method="POST" class="inline"
                                                      data-confirm="¿Quitar el gasto «{{ $gasto->concepto }}»? El monto vuelve a contarse como efectivo en caja.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icono-rojo" title="Quitar gasto" aria-label="Quitar gasto {{ $gasto->concepto }}">
                                                        <x-icon name="trash" class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $gestiona ? 5 : 4 }}" class="tabla-vacia">No hay gastos en estas fechas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($gestiona)
                    <p class="px-4 sm:px-6 py-3 text-[11px] text-stone-500 dark:text-stone-400 border-t border-stone-200 dark:border-stone-700">
                        Un gasto anotado por error se puede quitar el mismo día, mientras la caja siga abierta.
                    </p>
                @endif
            </section>

            <section class="tarjeta" aria-labelledby="titulo-por-tipo">
                <h2 id="titulo-por-tipo" class="titulo-tarjeta mb-3">Por tipo de gasto</h2>
                @if($porTipo->isEmpty())
                    <p class="text-sm text-stone-500 dark:text-stone-400">Sin gastos en este periodo.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($porTipo as $fila)
                            @php $porcentaje = $totalEquivalente > 0 ? round($fila['total'] / $totalEquivalente * 100) : 0; @endphp
                            <li>
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $fila['nombre'] }} <span class="font-normal text-stone-500 dark:text-stone-400">({{ $fila['cantidad'] }})</span></span>
                                    <span class="font-bold text-stone-800 dark:text-stone-100 whitespace-nowrap">@usd($fila['total']) <span class="text-xs font-normal text-stone-500 dark:text-stone-400">{{ $porcentaje }}%</span></span>
                                </div>
                                <div class="mt-1 h-1.5 rounded bg-stone-100 dark:bg-stone-700 overflow-hidden">
                                    <div class="h-full bg-rose-500 dark:bg-rose-400" style="width: {{ $porcentaje }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="ayuda-campo mt-3">Montos en Bs. convertidos a $ con la tasa del día en que se pagaron.</p>
                @endif
            </section>
        </div>
    </div>

    @if($puedeAnotar)
        {{-- ?anotar=1 llega desde la Caja ("Anotar gasto"): abre la ventana al cargar --}}
        <x-ventana id="ventana-gasto" titulo="Anotar gasto" subtitulo="Sale del efectivo de la caja de hoy y se resta al cerrarla." :abierta="request()->boolean('anotar')">
            <form action="{{ route('gastos.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_ventana" value="ventana-gasto">
                <div>
                    <label class="etiqueta" for="gasto-concepto">Concepto</label>
                    <input type="text" id="gasto-concepto" name="concepto" value="{{ old('concepto') }}" required minlength="3" maxlength="150"
                           placeholder="Ej. Flete del camión al mercado" class="campo @error('concepto') border-rose-500 @enderror">
                    @error('concepto') <p class="error-campo">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="etiqueta" for="gasto-categoria">Tipo de gasto</label>
                    <select id="gasto-categoria" name="categoria" required class="campo @error('categoria') border-rose-500 @enderror">
                        <option value="">Selecciona…</option>
                        @foreach(\App\Models\Gasto::CATEGORIAS as $valor => $nombre)
                            <option value="{{ $valor }}" @selected(old('categoria') === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria') <p class="error-campo">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="etiqueta" for="gasto-moneda">Se pagó en</label>
                        <select id="gasto-moneda" name="moneda" required class="campo @error('moneda') border-rose-500 @enderror">
                            <option value="USD" @selected(old('moneda', 'USD') === 'USD')>Dólares ($)</option>
                            <option value="VES" @selected(old('moneda') === 'VES') @disabled($sinTasa)>Bolívares (Bs.){{ $sinTasa ? ' — sin tasa' : '' }}</option>
                        </select>
                        @error('moneda') <p class="error-campo">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="etiqueta" for="gasto-monto">Monto</label>
                        <input type="number" id="gasto-monto" name="monto" value="{{ old('monto') }}" required step="0.01" min="0.01" max="9999999999"
                               inputmode="decimal" placeholder="0.00" class="campo @error('monto') border-rose-500 @enderror">
                        @error('monto') <p class="error-campo">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="ayuda-campo">Efectivo en caja ahora: @usd($disponible['usd']) / @ves($disponible['ves']). El gasto no puede ser mayor.</p>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                    <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
                    <button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> Anotar gasto</button>
                </div>
            </form>
        </x-ventana>
    @endif
@endsection
