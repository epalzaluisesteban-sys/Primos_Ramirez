@extends('layouts.app')
@section('title', 'Cómo usar el sistema')

@php
    $usuario = auth()->user();
    $esEncargado = $usuario->esEncargadoOSuperior();
    $tasaReal = \App\Support\Moneda::tasa();
    // Sin tasa cargada se usa una de ejemplo para poder mostrar los bolívares
    $tasa = $tasaReal > 0 ? $tasaReal : 40.0;

    $capitulos = [
        [
            'id' => 'inicio', 'titulo' => 'Antes de empezar', 'quien' => 'todos', 'pantalla' => 'Barra de arriba',
            'texto' => [
                'Arriba está el menú con las secciones. Cada quien ve solo las que le tocan según su rol.',
                'La tasa BCV del día se carga sola y con ella se pasan los dólares a bolívares. La campana avisa de productos agotados, con poco stock o por vencer.',
            ],
            'regla' => 'Todo se lleva en dólares. Los bolívares se calculan con la tasa del momento y quedan guardados en cada venta y en cada abono.',
        ],
        [
            'id' => 'abrir-caja', 'titulo' => 'Abrir la caja', 'quien' => 'todos', 'pantalla' => 'Caja',
            'texto' => [
                'Es lo primero del día: cuenta el efectivo con el que empiezas (el fondo), en dólares y en bolívares, y abre la caja.',
            ],
            'regla' => 'Sin la caja abierta no se puede vender ni cobrar abonos. Hay una sola caja por día.',
        ],
        [
            'id' => 'compra', 'titulo' => 'Comprar mercancía', 'quien' => 'encargado', 'pantalla' => 'Compras',
            'texto' => [
                'Cuando llega mercancía del proveedor se registra como compra: el stock sube y el sistema calcula el costo promedio del producto.',
                'Con ese costo se sabe cuánto se gana en cada venta.',
            ],
            'regla' => 'El stock solo cambia por compras, ventas, mermas o ajustes con motivo. Así el kardex siempre cuadra con lo que hay.',
        ],
        [
            'id' => 'contado', 'titulo' => 'Vender de contado', 'quien' => 'todos', 'pantalla' => 'Nueva venta',
            'texto' => [
                'Elige el producto, la cantidad y en qué moneda te pagan. Al registrar, el stock baja y el efectivo entra a la caja.',
            ],
            'regla' => 'Lo que se vende por unidad, caja, atado o saco va sin decimales. Lo que se vende por kg, lb o litro acepta hasta 3 decimales.',
        ],
        [
            'id' => 'fiado', 'titulo' => 'Vender fiado', 'quien' => 'todos', 'pantalla' => 'Nueva venta',
            'texto' => [
                'Si el cliente se lleva la mercancía y paga después, elige «Fiado» y el cliente. No entra dinero a la caja: sube lo que el cliente debe.',
            ],
            'regla' => 'Si el cliente tiene límite de crédito, el sistema no deja fiarle más de lo que le queda disponible.',
        ],
        [
            'id' => 'abono', 'titulo' => 'Cobrar un abono', 'quien' => 'encargado', 'pantalla' => 'Ficha del cliente',
            'texto' => [
                'Cuando el cliente paga lo que debe, todo o una parte, se registra en su ficha. Puede pagar en dólares o en bolívares; la deuda se descuenta en dólares con la tasa del día.',
            ],
            'regla' => 'El abono es efectivo que entra a la caja de hoy, por eso también necesita la caja abierta. No puede ser mayor a lo que se debe.',
        ],
        [
            'id' => 'merma', 'titulo' => 'Mermas y ajustes', 'quien' => 'encargado', 'pantalla' => 'Mermas',
            'texto' => [
                'Lo que se daña, se vence o se pierde se anota como merma, con su motivo: el stock baja sin que haya venta.',
                'Si al contar la mercancía hay una diferencia, se corrige desde «Editar producto», también con motivo (es un ajuste).',
            ],
            'regla' => 'El stock nunca se cambia a mano sin motivo: cada movimiento queda en el kardex del producto.',
        ],
        [
            'id' => 'anular', 'titulo' => 'Corregir o anular', 'quien' => 'encargado', 'pantalla' => 'Ventas',
            'texto' => [
                'Una venta del día se puede editar o anular mientras la caja siga abierta. Al anularla, la mercancía vuelve al inventario.',
            ],
            'regla' => 'Las ventas nunca se borran: se anulan con su motivo. Una venta anulada no cuenta en la caja, los reportes ni el saldo del cliente.',
        ],
        [
            'id' => 'cierre', 'titulo' => 'Cerrar la caja', 'quien' => 'todos', 'pantalla' => 'Caja',
            'texto' => [
                'Al final del día cuenta el efectivo del cajón, en dólares y en bolívares. El sistema lo compara con lo que debería haber y dice si sobra o falta.',
            ],
            'regla' => 'Esperado = fondo inicial + ventas de contado + abonos del día. Con la caja cerrada no se vende más ese día; si se cerró por error, el Encargado puede reabrirla.',
        ],
        [
            'id' => 'revisar', 'titulo' => 'Revisar el negocio', 'quien' => 'encargado', 'pantalla' => 'Reportes',
            'texto' => [
                'Con lo registrado, el sistema arma el kardex de cada producto, el reporte de ganancias, las estadísticas y el historial. Todo se puede bajar en Excel o PDF.',
            ],
            'regla' => 'Lo vendido de productos sin compras no tiene costo conocido: no entra en la ganancia y se avisa aparte.',
        ],
    ];
@endphp

@push('head')
<style>
    /* Cursor de la animación: se desliza hasta el campo o botón que se va a usar */
    #guia-cursor { transition: transform .5s ease-in-out, opacity .2s; }
    .guia-resaltado { outline: 2px solid #f59e0b; outline-offset: 3px; border-radius: 6px; }
    .guia-pulsado { transform: scale(.96); }
    @keyframes guia-cambio { from { background-color: rgba(251, 191, 36, .35); } to { background-color: transparent; } }
    .guia-cambio { animation: guia-cambio 1s ease-out; }
    /* Al seguir el cursor en el celular, que el campo no quede tapado por el estado fijo de arriba */
    @media (max-width: 1023px) {
        #guia-escenario [data-campo], #guia-escenario [data-accion], #guia-escenario [data-zona] { scroll-margin-top: 15rem; scroll-margin-bottom: 5rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        #guia-cursor { display: none; }
        .guia-cambio { animation: none; }
    }
</style>
@endpush

@section('content')
<div class="pagina" id="guia" data-tasa="{{ $tasa }}" data-encargado="{{ $esEncargado ? '1' : '0' }}">
    <x-encabezado titulo="Cómo usar el sistema"
                  subtitulo="El día de trabajo paso a paso, de abrir la caja a cerrarla. Mira cada capítulo y después practica." />

    <div class="aviso-azul mb-4">
        <x-icon name="lock" class="w-4 h-4 shrink-0 mt-0.5" />
        <p>
            <strong>Modo práctica: nada de lo que hagas aquí se guarda.</strong>
            <span class="hidden sm:inline">
                El tomate, el aguacate, el proveedor y el cliente son de ejemplo.
                @if($tasaReal > 0)
                    Los bolívares se calculan con la última tasa cargada (@ves($tasa) por dólar).
                @else
                    Como todavía no hay tasa cargada, se usa una de ejemplo (@ves($tasa) por dólar).
                @endif
            </span>
        </p>
    </div>

    @unless($esEncargado)
        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300 mb-3">
            <input type="checkbox" id="guia-solo-mios" class="casilla" checked>
            Ver solo lo que me toca: abrir la caja, vender y cerrarla
        </label>
    @endunless

    {{-- En el celular los capítulos van en una sola fila que se desliza de lado, para no empujar la pantalla de muestra hacia abajo --}}
    <ol id="guia-capitulos" class="flex gap-1.5 mb-4 overflow-x-auto -mx-4 px-4 [scrollbar-width:none] sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0" aria-label="Capítulos">
        @foreach($capitulos as $i => $cap)
            <li data-capitulo-item data-quien="{{ $cap['quien'] }}" class="shrink-0">
                <button type="button" data-ir="{{ $i }}" class="btn-secundario btn-sm whitespace-nowrap" title="{{ $cap['titulo'] }}">
                    <span class="font-bold">{{ $i + 1 }}</span><span>{{ $cap['titulo'] }}</span>
                </button>
            </li>
        @endforeach
    </ol>

    {{-- La pantalla de muestra va primero en el HTML: en el celular se ve antes que la explicación del capítulo --}}
    <div class="grid lg:grid-cols-3 gap-4 items-start">
        <section class="tarjeta p-0 lg:col-span-2 lg:col-start-1 lg:row-start-1" aria-label="Pantalla de muestra">
            <div class="flex items-center justify-between gap-3 px-4 py-2.5 border-b border-stone-200 dark:border-stone-700">
                <p class="text-xs text-stone-500 dark:text-stone-400">Pantalla de muestra: <span id="guia-pantalla" class="font-semibold text-stone-700 dark:text-stone-200"></span></p>
                <span class="insignia-azul">Práctica</span>
            </div>

            {{-- En el celular el estado y la narración quedan fijos arriba mientras la pantalla de muestra se desplaza --}}
            {{-- En el celular cada dato va en una línea (etiqueta y valor juntos) para que lo fijo ocupe poco --}}
            <div class="sticky top-16 z-20 lg:static bg-white dark:bg-stone-800 border-b border-stone-200 dark:border-stone-700">
            <dl id="guia-estado" class="flex flex-wrap gap-x-4 gap-y-1 px-3 py-2 sm:grid sm:grid-cols-5 sm:gap-0 sm:p-0 border-b border-stone-200 dark:border-stone-700 text-sm">
                <div class="flex items-baseline gap-1.5 sm:block sm:px-4 sm:py-2.5"><dt class="cifra-etiqueta">Caja</dt><dd class="sm:mt-1" data-estado="caja"></dd></div>
                <div class="flex items-baseline gap-1.5 sm:block sm:px-4 sm:py-2.5"><dt class="cifra-etiqueta">Efectivo<span class="hidden sm:inline"> en caja</span></dt><dd class="sm:mt-1 font-semibold text-stone-800 dark:text-stone-100" data-estado="efectivo"></dd></div>
                <div class="flex items-baseline gap-1.5 sm:block sm:px-4 sm:py-2.5"><dt class="cifra-etiqueta">Tomate</dt><dd class="sm:mt-1 font-semibold text-stone-800 dark:text-stone-100" data-estado="tomate"></dd></div>
                <div class="flex items-baseline gap-1.5 sm:block sm:px-4 sm:py-2.5"><dt class="cifra-etiqueta">Aguacate</dt><dd class="sm:mt-1 font-semibold text-stone-800 dark:text-stone-100" data-estado="aguacate"></dd></div>
                <div class="flex items-baseline gap-1.5 sm:block sm:px-4 sm:py-2.5"><dt class="cifra-etiqueta">Cliente debe</dt><dd class="sm:mt-1 font-semibold text-stone-800 dark:text-stone-100" data-estado="saldo"></dd></div>
            </dl>
            <div class="px-3 py-2 sm:px-4 sm:py-2.5 bg-stone-50 dark:bg-stone-900/60">
                <p id="guia-narracion" class="text-sm font-semibold text-stone-800 dark:text-stone-100 min-h-[2.5rem]" aria-live="polite"></p>
                <p id="guia-pista" class="ayuda-campo"></p>
            </div>
            </div>

            <div id="guia-escenario" class="relative p-4 sm:p-5">
                @foreach($capitulos as $cap)
                    <div data-capitulo="{{ $cap['id'] }}" data-pantalla="{{ $cap['pantalla'] }}" hidden>
                        @if(in_array($cap['id'], ['contado', 'fiado']))
                            @include('ayuda._venta', ['prefijo' => $cap['id']])
                        @else
                            @include('ayuda._' . $cap['id'])
                        @endif
                    </div>
                @endforeach

                <div id="guia-cursor" class="pointer-events-none absolute left-0 top-0 z-10 opacity-0" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="w-6 h-6"><path d="M5 3l14 8.2-6.2 1.3L10 19z" fill="#1c1917" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </div>
            </div>

            {{-- En el celular los botones quedan fijos abajo, cinco iguales con el ícono arriba del texto --}}
            @php $botonGuia = 'btn-sm max-sm:flex-col max-sm:gap-0.5 max-sm:px-0.5 max-sm:py-1.5 max-sm:text-[11px] max-sm:leading-tight'; @endphp
            <div class="sticky bottom-0 z-20 lg:static bg-white dark:bg-stone-800 border-t border-stone-200 dark:border-stone-700 px-2 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] sm:px-4 sm:pt-3 sm:pb-2.5">
                <div class="grid grid-cols-5 gap-1.5 sm:flex sm:items-center sm:gap-2">
                    <button type="button" id="guia-anterior" class="btn-secundario {{ $botonGuia }}"><x-icon name="arrow-left" class="w-4 h-4" /> Anterior</button>
                    <button type="button" id="guia-pausa" class="btn-secundario {{ $botonGuia }}"><x-icon name="pause" class="w-4 h-4" /> <span>Pausa</span></button>
                    <button type="button" id="guia-repetir" class="btn-secundario {{ $botonGuia }}"><x-icon name="repetir" class="w-4 h-4" /> Repetir</button>
                    <button type="button" id="guia-practicar" class="btn-oscuro {{ $botonGuia }}"><x-icon name="edit" class="w-4 h-4" /> Practicar</button>
                    <button type="button" id="guia-siguiente" class="btn-primario {{ $botonGuia }} max-sm:flex-col-reverse sm:ml-auto">Siguiente <x-icon name="arrow-right" class="w-4 h-4" /></button>
                </div>
            </div>
            <label class="flex items-center gap-2 px-3 pb-3 sm:px-4 text-xs text-stone-600 dark:text-stone-400">
                <input type="checkbox" id="guia-seguir" class="casilla" checked>
                Pasar solo al siguiente capítulo al terminar
            </label>
        </section>

        <aside class="tarjeta p-4 lg:col-start-3 lg:row-start-1">
            @foreach($capitulos as $i => $cap)
                <div data-texto="{{ $cap['id'] }}" hidden>
                    <p class="cifra-etiqueta">Capítulo {{ $i + 1 }} de {{ count($capitulos) }}</p>
                    <h2 class="titulo-tarjeta mt-1">{{ $cap['titulo'] }}</h2>
                    <p class="mt-2">
                        @if($cap['quien'] === 'todos')
                            <span class="insignia-verde">Lo hacen todos</span>
                        @else
                            <span class="insignia-ambar">Encargado o Dueño</span>
                        @endif
                    </p>
                    <div class="mt-3 space-y-2 text-sm text-stone-600 dark:text-stone-300">
                        @foreach($cap['texto'] as $parrafo)
                            <p>{{ $parrafo }}</p>
                        @endforeach
                        @if($cap['quien'] === 'encargado' && !$esEncargado)
                            <p class="text-stone-500 dark:text-stone-400">Tú no lo haces, pero conviene saber cómo funciona.</p>
                        @endif
                    </div>
                    <div class="caja-suave p-3 mt-4">
                        <p class="titulo-seccion mb-1">Regla del negocio</p>
                        <p class="text-sm text-stone-700 dark:text-stone-200">{{ $cap['regla'] }}</p>
                    </div>
                </div>
            @endforeach
        </aside>
    </div>

    <template id="guia-icono-ok"><x-icon name="check-circle" class="w-4 h-4 shrink-0 mt-0.5" /></template>
    <template id="guia-icono-error"><x-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" /></template>
    <template id="guia-icono-pausa"><x-icon name="pause" class="w-4 h-4" /></template>
    <template id="guia-icono-seguir"><x-icon name="play" class="w-4 h-4" /></template>
</div>
@endsection

@push('scripts')
    @include('ayuda._script')
@endpush
