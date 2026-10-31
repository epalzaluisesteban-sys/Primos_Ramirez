<!DOCTYPE html>
<html lang="es">
<head>
    <script>
        // Se ejecuta antes del primer paint para evitar parpadeo del tema incorrecto.
        (function () {
            var guardado = localStorage.getItem('tema');
            var prefiereOscuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (guardado === 'oscuro' || (!guardado && prefiereOscuro)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Los Primos Ramírez')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')

    <style>
        body { background: #FBF7EE; }
        .font-display { font-family: 'Manrope', sans-serif; }
        .market-stripe { background: linear-gradient(90deg, #16a34a 0%, #16a34a 33%, #f97316 33%, #f97316 66%, #dc2626 66%, #dc2626 100%); }

        /* Impresión: solo se imprime el contenido de la página, sin nav ni controles de acción. */
        @media print {
            .market-stripe, nav, .no-print { display: none !important; }
            body { background: #fff; }
            .shadow-sm, .shadow-md { box-shadow: none !important; }
        }

        /* Animaciones globales */
        @keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in-up { animation: fade-in-up .35s ease-out; }

        @keyframes scale-in { from { opacity: 0; transform: scale(.95); } to { opacity: 1; transform: scale(1); } }
        .animate-scale-in { animation: scale-in .2s ease-out; }

        @keyframes toast-in { from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: translateX(0); } }
        .animate-toast-in { animation: toast-in .25s ease-out; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin .8s linear infinite; }
    </style>
</head>
<body class="font-sans antialiased min-h-screen text-stone-800 dark:bg-stone-950 dark:text-stone-200 transition-colors">

    <div class="market-stripe h-1.5 w-full"></div>

    @auth
        @include('partials.nav')
    @endauth

    <main class="animate-fade-in-up">
        @yield('content')
    </main>

    <!-- Contenedor de Toasts -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-[150] flex flex-col gap-2 w-full max-w-sm no-print"></div>

    <!-- Modal de Confirmación (reemplaza confirm() nativo) -->
    <div id="modal-confirmacion" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 no-print">
        <div class="absolute inset-0 bg-stone-900/50 dark:bg-black/60 backdrop-blur-sm" data-modal-cancelar></div>
        <div class="relative bg-white dark:bg-stone-800 rounded-2xl shadow-xl border border-stone-200 dark:border-stone-700 max-w-sm w-full p-6 animate-scale-in">
            <div class="flex items-start gap-3 mb-5">
                <span class="shrink-0 w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <x-icon name="alert-triangle" class="w-5 h-5" />
                </span>
                <div>
                    <p class="font-display font-bold text-stone-900 dark:text-stone-100">Confirmar acción</p>
                    <p id="modal-confirmacion-texto" class="text-sm text-stone-600 dark:text-stone-300 mt-1"></p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" data-modal-cancelar class="px-4 py-2 rounded-xl text-sm font-semibold text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-700 transition">Cancelar</button>
                <button type="button" id="modal-confirmacion-aceptar" class="px-4 py-2 rounded-xl text-sm font-semibold bg-rose-600 hover:bg-rose-700 text-white transition">Sí, continuar</button>
            </div>
        </div>
    </div>

    <!-- Barra de carga -->
    <div id="loader-bar" class="fixed top-0 left-0 h-1 bg-emerald-600 dark:bg-emerald-500 z-[300] w-0 opacity-0 transition-all duration-300 pointer-events-none"></div>

    <script>
        // Filtro de texto reutilizable para listados (productos, proveedores, clientes).
        // Oculta las filas <tbody><tr> cuyo texto no contenga lo escrito en el input.
        function filtrarTabla(idInput, idTabla) {
            const texto = document.getElementById(idInput).value.trim().toLowerCase();
            const filas = document.querySelectorAll('#' + idTabla + ' tbody tr');

            filas.forEach(fila => {
                const contenido = fila.textContent.toLowerCase();
                fila.style.display = contenido.includes(texto) ? '' : 'none';
            });
        }

        // Interruptor de tema claro/oscuro (botón en el navbar)
        function alternarTema() {
            const esOscuro = document.documentElement.classList.toggle('dark');
            localStorage.setItem('tema', esOscuro ? 'oscuro' : 'claro');
        }

        // ==========================================
        // Toasts
        // ==========================================
        function mostrarToast(mensaje, tipo) {
            tipo = tipo || 'success';
            const estilos = {
                success: { icono: 'check-circle', clases: 'bg-emerald-600' },
                error: { icono: 'alert-triangle', clases: 'bg-rose-600' },
            };
            const estilo = estilos[tipo] || estilos.success;

            const contenedor = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'animate-toast-in flex items-start gap-2.5 text-white text-sm font-semibold px-4 py-3 rounded-xl shadow-lg ' + estilo.clases;
            toast.innerHTML = '<svg class="w-4 h-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' +
                (estilo.icono === 'check-circle' ? '<circle cx="12" cy="12" r="8.5"/><path d="M8 12.3l2.4 2.4L16.2 9"/>' : '<path d="M12 4.5 21 19.5H3z"/><path d="M12 10v3.5"/><circle cx="12" cy="16.5" r=".9" fill="currentColor" stroke="none"/>') +
                '</svg><span class="flex-1">' + mensaje + '</span>';

            contenedor.appendChild(toast);
            setTimeout(function () {
                toast.style.transition = 'opacity .3s, transform .3s';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(16px)';
                setTimeout(function () { toast.remove(); }, 300);
            }, 4200);
        }

        // ==========================================
        // Modal de confirmación: intercepta cualquier <form data-confirm="...">
        // ==========================================
        (function () {
            const modal = document.getElementById('modal-confirmacion');
            const texto = document.getElementById('modal-confirmacion-texto');
            const btnAceptar = document.getElementById('modal-confirmacion-aceptar');
            let formPendiente = null;

            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (form.hasAttribute && form.hasAttribute('data-confirm') && !form.dataset.confirmado) {
                    e.preventDefault();
                    formPendiente = form;
                    texto.textContent = form.getAttribute('data-confirm');
                    modal.classList.remove('hidden');
                }
            });

            btnAceptar.addEventListener('click', function () {
                modal.classList.add('hidden');
                if (formPendiente) {
                    formPendiente.dataset.confirmado = '1';
                    if (formPendiente.requestSubmit) { formPendiente.requestSubmit(); } else { formPendiente.submit(); }
                    formPendiente = null;
                }
            });

            modal.querySelectorAll('[data-modal-cancelar]').forEach(function (el) {
                el.addEventListener('click', function () {
                    modal.classList.add('hidden');
                    formPendiente = null;
                });
            });
        })();

        // ==========================================
        // Loader: barra superior + spinner en el botón que disparó el envío
        // ==========================================
        (function () {
            const barra = document.getElementById('loader-bar');
            const spinnerSvg = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 3.5a8.5 8.5 0 1 0 8.5 8.5"/></svg>';

            function iniciarBarra() {
                barra.style.opacity = '1';
                barra.style.width = '0%';
                requestAnimationFrame(function () { barra.style.width = '80%'; });
            }

            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (form.hasAttribute && form.hasAttribute('data-confirm') && !form.dataset.confirmado) return; // el modal decide primero
                iniciarBarra();
                const boton = form.querySelector('button[type="submit"]');
                if (boton && !boton.disabled) {
                    boton.disabled = true;
                    boton.style.opacity = '.7';
                    boton.innerHTML = spinnerSvg + ' ' + boton.textContent.trim();
                }
            });

            document.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link || link.target === '_blank' || link.href.indexOf('javascript:') === 0 || e.metaKey || e.ctrlKey) return;
                if (link.origin !== window.location.origin) return;
                iniciarBarra();
            });
        })();

        @if(session('success'))
            document.addEventListener('DOMContentLoaded', function () { mostrarToast(@json(session('success')), 'success'); });
        @endif
        @if($errors->any())
            document.addEventListener('DOMContentLoaded', function () { mostrarToast(@json($errors->first()), 'error'); });
        @endif
    </script>

    @stack('scripts')
</body>
</html>
