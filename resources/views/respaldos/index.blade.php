@extends('layouts.app')

@section('title', 'Respaldo y restauración')

@section('content')
    @php
        $haceCuanto = fn (int $dias) => $dias === 0 ? 'hoy' : ($dias === 1 ? 'ayer' : "hace {$dias} días");
    @endphp
    <div class="pagina">
        <x-encabezado titulo="Respaldo y restauración" subtitulo="Guarda una copia de toda la información del sistema o recupérala desde un respaldo." />

        {{-- Escritorio: crear e historial a la izquierda, restaurar (poco frecuente y delicado) en la columna derecha.
             Celular: crear, restaurar, historial. --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <section class="tarjeta lg:col-span-2" aria-labelledby="titulo-crear">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="titulo-crear" class="titulo-tarjeta">Crear respaldo</h2>
                    @if($diasSinRespaldo === null)
                        <span class="insignia-roja">Sin respaldos</span>
                    @elseif($diasSinRespaldo > \App\Services\RespaldoService::DIAS_AVISO)
                        <span class="insignia-ambar">Último {{ $haceCuanto($diasSinRespaldo) }}</span>
                    @else
                        <span class="insignia-verde">Al día</span>
                    @endif
                </div>
                <p class="text-sm text-stone-600 dark:text-stone-300 mt-1">
                    Descarga un archivo de texto (.json) con toda la información y las fotos. Guárdalo fuera de esta computadora: memoria USB, Drive o correo.
                </p>

                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                    <div class="caja-suave px-4 py-3">
                        <dt class="cifra-etiqueta">Último respaldo</dt>
                        @if($ultimoManual)
                            <dd class="font-semibold text-stone-800 dark:text-stone-100 mt-1">{{ $ultimoManual->created_at->format('d/m/Y h:i A') }}</dd>
                            <dd class="text-xs text-stone-500 dark:text-stone-400">{{ ucfirst($haceCuanto($diasSinRespaldo)) }}, por {{ $ultimoManual->autor }}</dd>
                        @else
                            <dd class="font-semibold text-stone-800 dark:text-stone-100 mt-1">Ninguno</dd>
                            <dd class="text-xs text-stone-500 dark:text-stone-400">Haz el primero hoy</dd>
                        @endif
                    </div>
                    <div class="caja-suave px-4 py-3">
                        <dt class="cifra-etiqueta">Registros a guardar</dt>
                        <dd class="font-semibold text-stone-800 dark:text-stone-100 mt-1">{{ number_format($actual['registros'], 0, ',', '.') }}</dd>
                        <dd class="text-xs text-stone-500 dark:text-stone-400">Ventas, inventario, clientes, caja y más</dd>
                    </div>
                    <div class="caja-suave px-4 py-3">
                        <dt class="cifra-etiqueta">Fotos a guardar</dt>
                        <dd class="font-semibold text-stone-800 dark:text-stone-100 mt-1">{{ $actual['fotos'] }}</dd>
                        <dd class="text-xs text-stone-500 dark:text-stone-400">Productos, clientes, personal y categorías</dd>
                    </div>
                </dl>

                <form action="{{ route('respaldos.store') }}" method="POST" class="mt-5">
                    @csrf
                    <button type="submit" class="btn-primario w-full sm:w-auto">
                        <x-icon name="download" class="w-4 h-4" /> Crear y descargar respaldo
                    </button>
                </form>
            </section>

            <section class="tarjeta lg:self-start lg:col-start-3 lg:row-start-1 lg:row-span-2" aria-labelledby="titulo-restaurar">
                <h2 id="titulo-restaurar" class="titulo-tarjeta">Restaurar un respaldo</h2>
                <p class="text-sm text-stone-600 dark:text-stone-300 mt-1">Recupera la información desde un archivo de respaldo.</p>

                <ol class="mt-4 space-y-3 text-sm">
                    @foreach([
                        ['Sube el archivo', 'El .json que descargaste (también sirven los .tar.gz anteriores).'],
                        ['Revisa qué contiene', 'Verás cuántos registros trae comparado con lo que hay ahora.'],
                        ['Confirma', 'Escribe RESTAURAR y tu contraseña. Hasta ese momento no cambia nada.'],
                    ] as $i => [$paso, $detalle])
                        <li class="flex gap-3">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-stone-100 text-stone-700 dark:bg-stone-700 dark:text-stone-200 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                            <span>
                                <span class="block font-semibold text-stone-800 dark:text-stone-100">{{ $paso }}</span>
                                <span class="block text-xs text-stone-500 dark:text-stone-400">{{ $detalle }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>

                <form action="{{ route('respaldos.preparar') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-3">
                    @csrf
                    <div>
                        <label class="etiqueta" for="archivo">Archivo de respaldo</label>
                        <input id="archivo" type="file" name="archivo" accept=".json,application/json,.gz,.tar.gz,application/gzip" required
                               class="campo-archivo @error('archivo') border-rose-500 @enderror">
                        @error('archivo') <p class="error-campo">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-secundario w-full">
                        <x-icon name="upload" class="w-4 h-4" /> Revisar respaldo
                    </button>
                </form>

                <div class="aviso-ambar mt-4 text-xs">
                    <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0" />
                    <span>Restaurar <strong>reemplaza toda la información actual</strong>. Antes se guarda sola una copia de lo que hay ahora, por si necesitas volver atrás.</span>
                </div>
            </section>

            <section class="tarjeta p-0 overflow-hidden lg:col-span-2" aria-labelledby="titulo-historial">
                <div class="flex items-center gap-2 px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                    <h2 id="titulo-historial" class="titulo-tarjeta">Respaldos guardados en el servidor</h2>
                    <span class="insignia-gris">{{ $respaldos->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Hecho por</th>
                                <th class="text-right">Contenido</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($respaldos as $r)
                                @php $existe = $r->existeArchivo(); @endphp
                                <tr class="{{ $existe ? '' : 'opacity-60' }}">
                                    <td class="whitespace-nowrap">
                                        <span class="block text-stone-800 dark:text-stone-100">{{ $r->created_at->format('d/m/Y') }}</span>
                                        <span class="block text-xs text-stone-500 dark:text-stone-400">{{ $r->created_at->format('h:i A') }}</span>
                                    </td>
                                    <td>
                                        @if($r->tipo === 'automatico')
                                            <span class="insignia-azul">Automático</span>
                                            @if($r->nota)<p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1 max-w-56">{{ $r->nota }}</p>@endif
                                        @else
                                            <span class="insignia-verde">Manual</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->autor }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        <span class="block">{{ $r->registros }} registros · {{ $r->fotos }} fotos</span>
                                        <span class="block text-xs text-stone-500 dark:text-stone-400">{{ $r->tamanoLegible() }}</span>
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            @if($existe)
                                                <a href="{{ route('respaldos.descargar', $r) }}" class="btn-icono-verde" title="Descargar" aria-label="Descargar">
                                                    <x-icon name="download" class="w-4 h-4" />
                                                </a>
                                            @else
                                                <span class="insignia-gris" title="El servidor lo borró al actualizarse. Usa la copia que descargaste.">Ya no está</span>
                                            @endif
                                            <form action="{{ route('respaldos.destroy', $r) }}" method="POST" class="inline"
                                                  data-confirm="¿Eliminar del servidor el respaldo del {{ $r->created_at->format('d/m/Y h:i A') }}? Las copias que ya descargaste no se borran.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icono-rojo" title="Eliminar del servidor" aria-label="Eliminar del servidor">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="tabla-vacia">Todavía no hay respaldos. Crea el primero con el botón de arriba.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="px-6 py-3 border-t border-stone-200 dark:border-stone-700 text-[11px] text-stone-500 dark:text-stone-400">
                    En el servidor de internet estas copias pueden borrarse cuando se actualiza el sistema. El respaldo seguro es el archivo que descargas.
                </p>
            </section>
        </div>
    </div>
@endsection

@if(session('descargar'))
    @push('scripts')
        <script>
            // El respaldo recién creado se descarga solo
            window.addEventListener('load', function () {
                window.location.href = @json(route('respaldos.descargar', session('descargar')));
            });
        </script>
    @endpush
@endif
