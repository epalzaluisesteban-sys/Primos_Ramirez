<script>
/*
 * Guía "Cómo usar": todo pasa en memoria del navegador. No hay formularios ni peticiones al servidor,
 * así que nada de lo que se escriba aquí se guarda.
 *
 * Cada capítulo tiene un guion (pasos que la animación ejecuta) y acciones (lo que hace cada botón).
 * El estado con el que empieza un capítulo se obtiene corriendo, sin animación, los guiones de los
 * capítulos anteriores: así el recorrido es un solo día de trabajo y cada capítulo se puede repetir.
 */
(() => {
    const guia = document.getElementById('guia');
    const TASA = parseFloat(guia.dataset.tasa);
    const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const escenario = document.getElementById('guia-escenario');
    const cursor = document.getElementById('guia-cursor');
    const narracion = document.getElementById('guia-narracion');
    const pista = document.getElementById('guia-pista');
    const botonPausa = document.getElementById('guia-pausa');
    const botonPracticar = document.getElementById('guia-practicar');
    const casillaSeguir = document.getElementById('guia-seguir');
    const casillaSoloMios = document.getElementById('guia-solo-mios');
    const ids = [...document.querySelectorAll('[data-capitulo]')].map(el => el.dataset.capitulo);
    const CANCELADO = Symbol('cancelado');

    // Mismo formato que App\Support\Moneda y App\Support\Cantidad
    const miles = (fijo, sepMiles, sepDecimal) => {
        const [entero, decimales] = fijo.split('.');
        return entero.replace(/\B(?=(\d{3})+(?!\d))/g, sepMiles) + sepDecimal + decimales;
    };
    const signo = n => (n < -0.004 ? '−' : '');
    const usd = n => signo(n) + '$' + miles(Math.abs(n).toFixed(2), ',', '.');
    const ves = n => signo(n) + 'Bs. ' + miles(Math.abs(n).toFixed(2), '.', ',');
    const cantidad = n => String(parseFloat(n.toFixed(3)));
    const redondear = (n, d = 2) => Math.round(n * 10 ** d) / 10 ** d;
    const numero = texto => {
        const limpio = String(texto ?? '').trim().replace(',', '.');
        return limpio === '' || !/^\d*\.?\d+$/.test(limpio) ? NaN : parseFloat(limpio);
    };
    const unidadDe = (p, n) => (p.entero ? (n === 1 ? 'unidad' : 'unidades') : p.unidad);

    const estadoBase = () => ({
        caja: 'sin-abrir',
        fondo: { usd: 0, ves: 0 },
        cierre: null,
        productos: {
            tomate: { nombre: 'Tomate', unidad: 'kg', entero: false, precio: 2, stock: 40 },
            aguacate: { nombre: 'Aguacate', unidad: 'unidad', entero: true, precio: 0.8, stock: 60 },
        },
        cliente: { limite: 30, saldo: 0 },
        compras: [],
        ventas: [],
        abonos: [],
        movimientos: [
            { producto: 'tomate', concepto: 'Inventario inicial', cantidad: 40 },
            { producto: 'aguacate', concepto: 'Inventario inicial', cantidad: 60 },
        ],
    });

    const resumenCaja = e => {
        const contado = e.ventas.filter(v => !v.anulada && v.tipo === 'contado');
        const r = {
            contadoUsd: contado.filter(v => v.moneda === 'USD').reduce((s, v) => s + v.total, 0),
            contadoVes: contado.filter(v => v.moneda === 'VES').reduce((s, v) => s + v.totalVes, 0),
            abonosUsd: e.abonos.filter(a => a.moneda === 'USD').reduce((s, a) => s + a.montoMoneda, 0),
            abonosVes: e.abonos.filter(a => a.moneda === 'VES').reduce((s, a) => s + a.montoMoneda, 0),
        };
        r.esperadoUsd = redondear(e.fondo.usd + r.contadoUsd + r.abonosUsd);
        r.esperadoVes = redondear(e.fondo.ves + r.contadoVes + r.abonosVes);
        return r;
    };

    const costoPromedio = (e, producto) => {
        const compras = e.compras.filter(c => c.producto === producto);
        const unidades = compras.reduce((s, c) => s + c.cantidad, 0);
        return unidades > 0 ? compras.reduce((s, c) => s + c.cantidad * c.precio, 0) / unidades : null;
    };

    const resultado = (ok, texto) => ({ ok, texto });

    const errorCaja = (e, despues) => {
        if (e.caja === 'cerrada') return 'La caja de hoy ya fue cerrada. Si se cerró por error, un encargado puede reabrirla.';
        if (e.caja === 'sin-abrir') return 'Primero abre la caja del día (capítulo 2). Después podrás ' + despues + '.';
        return null;
    };

    // Revisa producto y cantidad como Producto::errorCantidad(); devuelve [producto, cantidad] o un mensaje
    const productoYCantidad = (e, v) => {
        const p = e.productos[v.producto];
        if (!p) return 'Elige el producto.';
        const n = numero(v.cantidad);
        if (!(n > 0)) return 'Escribe una cantidad mayor que 0.';
        if (p.entero && Math.abs(n - Math.round(n)) > 0.0005) {
            return p.nombre + ' se cuenta por unidad: escribe una cantidad sin decimales (ej. 1, 2, 3).';
        }
        return [p, n];
    };

    const registrarVenta = (e, v) => {
        const err = errorCaja(e, 'registrar ventas');
        if (err) return resultado(false, err);
        const pc = productoYCantidad(e, v);
        if (typeof pc === 'string') return resultado(false, pc);
        const [p, n] = pc;
        if (n > p.stock + 0.0005) {
            return resultado(false, 'Stock insuficiente: solo hay ' + cantidad(p.stock) + ' ' + unidadDe(p, p.stock) + ' de ' + p.nombre.toLowerCase() + '.');
        }
        const total = redondear(p.precio * n);
        const credito = v.tipoPago === 'credito';
        if (credito) {
            if (v.cliente !== 'ejemplo') return resultado(false, 'Para fiar, elige el cliente.');
            const disponible = Math.max(e.cliente.limite - e.cliente.saldo, 0);
            if (total > disponible + 0.005) {
                return resultado(false, 'Esta venta supera el crédito disponible del cliente: solo se le pueden fiar ' + usd(disponible)
                    + ' más (debe ' + usd(e.cliente.saldo) + ' de un límite de ' + usd(e.cliente.limite) + ') y la venta es de ' + usd(total) + '.');
            }
        }
        const venta = {
            n: e.ventas.length + 1, producto: v.producto, cantidad: n, total, totalVes: redondear(total * TASA),
            tipo: credito ? 'credito' : 'contado', moneda: credito ? 'USD' : v.moneda, anulada: false,
        };
        e.ventas.push(venta);
        p.stock = redondear(p.stock - n, 3);
        e.movimientos.push({ producto: v.producto, concepto: 'Venta #' + venta.n, cantidad: -n, venta: venta.n });
        if (credito) {
            e.cliente.saldo = redondear(e.cliente.saldo + total);
            return resultado(true, 'Venta #' + venta.n + ' fiada. El cliente ahora debe ' + usd(e.cliente.saldo) + '; a la caja no entró dinero.');
        }
        const cobrado = venta.moneda === 'VES' ? ves(venta.totalVes) : usd(total);
        return resultado(true, 'Venta #' + venta.n + ' registrada. Entraron ' + cobrado + ' a la caja y quedan ' + cantidad(p.stock) + ' ' + unidadDe(p, p.stock) + ' de ' + p.nombre.toLowerCase() + '.');
    };

    // Pasos del guion: ['decir', texto] · ['ver', zona, texto] · ['escribir'|'elegir', campo, valor, texto?] · ['pulsar', accion, texto?]
    const CAPITULOS = {
        'inicio': {
            guion: [
                ['decir', 'Esta es la barra de arriba, la misma que ves en el sistema.'],
                ['ver', 'menu', 'En el menú están las secciones. Cada quien ve solo las que le tocan según su rol.'],
                ['ver', 'tasa', 'La tasa BCV del día se carga sola. Con ella se pasan los dólares a bolívares.'],
                ['ver', 'campana', 'La campana avisa de productos agotados, con poco stock o por vencer.'],
                ['ver', 'avisos', 'Al tocar un aviso se abren esos productos para revisarlos.'],
                ['ver', 'avatar', 'En tu inicial están tu perfil, esta guía y el botón para salir.'],
                ['ver', 'roles', 'Y esto es lo que puede hacer cada rol.'],
            ],
            pintar(e, c) { salida(c, 'tasa').textContent = ves(TASA); },
        },
        'abrir-caja': {
            practica: 'Escribe el fondo inicial y presiona «Abrir caja del día».',
            guion: [
                ['ver', 'estado', 'Así está la caja al empezar el día: todavía sin abrir. Sin caja abierta no se vende.'],
                ['escribir', 'fondoUsd', '20', 'Escribe con cuántos dólares en efectivo empiezas.'],
                ['escribir', 'fondoVes', '500', 'Y con cuántos bolívares.'],
                ['pulsar', 'abrir', 'Presiona «Abrir caja del día».'],
                ['decir', 'La caja quedó abierta. Arriba, en el estado de práctica, se ve el efectivo con el que empieza.'],
            ],
            acciones: {
                abrir(e, v) {
                    if (e.caja !== 'sin-abrir') return resultado(false, 'Ya existe una caja para hoy. Solo se abre una por día.');
                    const fondoUsd = numero(v.fondoUsd);
                    const fondoVes = v.fondoVes.trim() === '' ? 0 : numero(v.fondoVes);
                    if (isNaN(fondoUsd)) return resultado(false, 'Escribe con cuántos dólares empiezas (puede ser 0).');
                    if (isNaN(fondoVes)) return resultado(false, 'Escribe los bolívares solo con números, por ejemplo 500 o 500,50.');
                    e.fondo = { usd: fondoUsd, ves: fondoVes };
                    e.caja = 'abierta';
                    return resultado(true, 'Caja abierta con ' + usd(fondoUsd) + ' y ' + ves(fondoVes) + '. Ya se puede vender.');
                },
            },
            pintar(e, c) { salida(c, 'estado-caja').innerHTML = insigniaCaja(e.caja); },
        },
        'compra': {
            practica: 'Elige producto y proveedor, escribe cantidad y precio, y presiona «Registrar compra».',
            guion: [
                ['decir', 'Llegaron 20 kg de tomate del proveedor. Hay que registrarlo como compra.'],
                ['elegir', 'producto', 'tomate', 'Elige el producto.'],
                ['elegir', 'proveedor', 'ejemplo', 'Elige quién te lo vendió.'],
                ['escribir', 'cantidad', '20', 'Escribe cuántos kg llegaron.'],
                ['escribir', 'precio', '1.20', 'Y cuánto pagaste por cada kg.'],
                ['pulsar', 'registrar', 'Presiona «Registrar compra».'],
                ['ver', 'costo', 'El stock subió de 40 a 60 kg y ya se conoce lo que cuesta el tomate: con eso se calcula la ganancia.'],
            ],
            acciones: {
                registrar(e, v) {
                    const pc = productoYCantidad(e, v);
                    if (typeof pc === 'string') return resultado(false, pc);
                    const [p, n] = pc;
                    if (v.proveedor !== 'ejemplo') return resultado(false, 'Elige el proveedor.');
                    const precio = numero(v.precio);
                    if (!(precio > 0)) return resultado(false, 'Escribe cuánto pagaste por cada ' + (p.entero ? 'unidad' : p.unidad) + '.');
                    const antes = p.stock;
                    p.stock = redondear(p.stock + n, 3);
                    e.compras.push({ producto: v.producto, cantidad: n, precio });
                    e.movimientos.push({ producto: v.producto, concepto: 'Compra', cantidad: n });
                    return resultado(true, 'Compra registrada. ' + p.nombre + ' pasó de ' + cantidad(antes) + ' a ' + cantidad(p.stock) + ' ' + unidadDe(p, p.stock) + '.');
                },
            },
            pintar(e, c) {
                const costo = costoPromedio(e, 'tomate');
                salida(c, 'costo').textContent = costo === null ? 'todavía sin compras' : usd(costo) + ' por kg';
                pintarAyudaCantidad(e, c);
            },
        },
        'contado': {
            practica: 'Elige el producto, escribe la cantidad y presiona «Registrar venta». Prueba con 2.5 aguacates para ver qué pasa.',
            guion: [
                ['decir', 'Un cliente se lleva 3 kg de tomate y paga en dólares.'],
                ['elegir', 'tipoPago', 'contado', 'Elige «De contado»: el cliente paga ahora.'],
                ['elegir', 'producto', 'tomate', 'Elige el producto.'],
                ['escribir', 'cantidad', '3', 'Escribe la cantidad. El tomate va por kg, así que acepta decimales como 2.5.'],
                ['elegir', 'moneda', 'USD', 'Elige en qué moneda te paga.'],
                ['ver', 'total', 'El total sale en dólares y en bolívares con la tasa del día.'],
                ['pulsar', 'registrar', 'Presiona «Registrar venta».'],
                ['decir', 'El tomate bajó de 60 a 57 kg y los $6.00 entraron a la caja.'],
            ],
            acciones: { registrar: registrarVenta },
            pintar: pintarVenta,
        },
        'fiado': {
            practica: 'Elige «Fiado», el cliente y el producto. Intenta pasarte del límite de $30.00 para ver el aviso.',
            guion: [
                ['decir', 'Ahora el cliente de ejemplo se lleva aguacates y paga después.'],
                ['elegir', 'tipoPago', 'credito', 'Elige «Fiado».'],
                ['elegir', 'cliente', 'ejemplo', 'Elige el cliente. Debajo se ve cuánto debe y cuánto se le puede fiar.'],
                ['elegir', 'producto', 'aguacate', 'Elige el producto.'],
                ['escribir', 'cantidad', '45', 'Pide 45 aguacates: son $36.00.'],
                ['pulsar', 'registrar', 'Presiona «Registrar venta».'],
                ['ver', 'mensaje', 'El sistema no deja: su límite es $30.00.'],
                ['escribir', 'cantidad', '25', 'Se lleva 25 ($20.00), que sí caben en su crédito.'],
                ['pulsar', 'registrar'],
                ['decir', 'El cliente ahora debe $20.00. A la caja no entró dinero: entra cuando pague.'],
            ],
            acciones: { registrar: registrarVenta },
            pintar: pintarVenta,
        },
        'abono': {
            practica: 'Elige la moneda, escribe cuánto paga el cliente y presiona «Registrar abono».',
            guion: [
                ['decir', 'El cliente de ejemplo vino a pagar una parte de lo que debe, en bolívares.'],
                ['ver', 'deuda', 'Su ficha muestra cuánto debe.'],
                ['elegir', 'moneda', 'VES', 'Elige la moneda en que paga.'],
                ['escribir', 'monto', () => (8 * TASA).toFixed(2), 'Paga el equivalente a $8.00.'],
                ['ver', 'equivale', 'El sistema lo pasa a dólares con la tasa del día.'],
                ['pulsar', 'registrar', 'Presiona «Registrar abono».'],
                ['decir', 'La deuda bajó a $12.00 y esos bolívares entraron a la caja de hoy.'],
            ],
            acciones: {
                registrar(e, v) {
                    const err = errorCaja(e, 'registrar el abono');
                    if (err) return resultado(false, err);
                    const monto = redondear(numero(v.monto));
                    if (!(monto >= 0.01)) return resultado(false, 'Escribe cuánto paga el cliente.');
                    const montoUsd = v.moneda === 'VES' ? redondear(monto / TASA) : monto;
                    if (montoUsd > e.cliente.saldo + 0.01) {
                        return resultado(false, 'El abono (' + usd(montoUsd) + ') no puede ser mayor al saldo pendiente (' + usd(e.cliente.saldo) + ').');
                    }
                    e.abonos.push({ moneda: v.moneda, montoMoneda: monto, usd: montoUsd });
                    e.cliente.saldo = Math.max(redondear(e.cliente.saldo - montoUsd), 0);
                    const pagado = v.moneda === 'VES' ? ves(monto) + ' (' + usd(montoUsd) + ')' : usd(monto);
                    return resultado(true, 'Abono de ' + pagado + ' registrado. El cliente ahora debe ' + usd(e.cliente.saldo) + '.');
                },
            },
            pintar(e, c) {
                salida(c, 'deuda').textContent = usd(e.cliente.saldo);
                const monto = numero(campo(c, 'monto').value);
                salida(c, 'equivale').textContent = campo(c, 'moneda').value === 'VES' && monto > 0
                    ? 'Equivale a ' + usd(monto / TASA) + ' con la tasa de ' + ves(TASA) + '.'
                    : '';
            },
        },
        'merma': {
            practica: 'Elige el producto, la cantidad perdida y el motivo, y presiona «Registrar merma».',
            guion: [
                ['decir', 'Al revisar la mercancía, 1.5 kg de tomate están dañados.'],
                ['elegir', 'producto', 'tomate', 'Elige el producto.'],
                ['escribir', 'cantidad', '1.5', 'Escribe cuánto se perdió.'],
                ['elegir', 'motivo', 'Se dañó', 'Elige el motivo.'],
                ['pulsar', 'registrar', 'Presiona «Registrar merma».'],
                ['decir', 'El stock bajó sin que haya venta y queda anotado con su motivo en el kardex.'],
            ],
            acciones: {
                registrar(e, v) {
                    const pc = productoYCantidad(e, v);
                    if (typeof pc === 'string') return resultado(false, pc);
                    const [p, n] = pc;
                    if (n > p.stock + 0.0005) return resultado(false, 'No puede perderse más de lo que hay: ' + cantidad(p.stock) + ' ' + unidadDe(p, p.stock) + '.');
                    if (!v.motivo) return resultado(false, 'Elige el motivo de la merma.');
                    p.stock = redondear(p.stock - n, 3);
                    e.movimientos.push({ producto: v.producto, concepto: 'Merma (' + v.motivo.toLowerCase() + ')', cantidad: -n });
                    return resultado(true, 'Merma registrada. Quedan ' + cantidad(p.stock) + ' ' + unidadDe(p, p.stock) + ' de ' + p.nombre.toLowerCase() + '.');
                },
            },
            pintar: pintarAyudaCantidad,
        },
        'anular': {
            practica: 'Elige una venta, escribe el motivo y presiona «Eliminar venta».',
            // La venta equivocada que se elimina en este capítulo
            preparar(e) {
                registrarVenta(e, { producto: 'tomate', cantidad: '10', tipoPago: 'contado', moneda: 'USD' });
            },
            guion: [
                ['decir', 'Alguien registró la venta #3 con 10 kg de tomate, pero eran 1 kg.'],
                ['ver', 'ventas', 'Las ventas del día aparecen en la lista de Ventas.'],
                ['elegir', 'venta', '3', 'Elige la venta equivocada.'],
                ['escribir', 'motivo', 'Eran 1 kg, no 10', 'Escribe por qué se elimina.'],
                ['pulsar', 'anular', 'Presiona «Eliminar venta».'],
                ['decir', 'Los 10 kg volvieron al inventario y la venta pasó a la Papelera con su motivo. Después se registra la venta correcta.'],
            ],
            acciones: {
                anular(e, v) {
                    const venta = e.ventas.find(x => x.n === parseInt(v.venta, 10));
                    if (!venta) return resultado(false, 'Elige la venta que quieres eliminar.');
                    if (venta.anulada) return resultado(false, 'Esa venta ya está en la papelera.');
                    if (e.caja !== 'abierta') return resultado(false, 'Solo se pueden eliminar ventas de hoy mientras la caja siga abierta.');
                    const p = e.productos[venta.producto];
                    p.stock = redondear(p.stock + venta.cantidad, 3);
                    if (venta.tipo === 'credito') e.cliente.saldo = redondear(e.cliente.saldo - venta.total);
                    venta.anulada = true;
                    return resultado(true, 'Venta #' + venta.n + ' enviada a la papelera. Volvieron ' + cantidad(venta.cantidad) + ' ' + unidadDe(p, venta.cantidad) + ' de ' + p.nombre.toLowerCase() + ' al inventario.');
                },
            },
            pintar(e, c) {
                const cuerpo = salida(c, 'ventas');
                cuerpo.innerHTML = '';
                e.ventas.forEach(v => {
                    const p = e.productos[v.producto];
                    const fila = cuerpo.insertRow();
                    if (v.anulada) fila.className = 'opacity-60';
                    fila.insertCell().textContent = '#' + v.n;
                    fila.insertCell().textContent = p.nombre + ' x' + cantidad(v.cantidad);
                    fila.insertCell().innerHTML = v.tipo === 'credito'
                        ? '<span class="insignia-naranja">Fiado</span>'
                        : '<span class="insignia-gris">Contado ' + (v.moneda === 'VES' ? 'Bs.' : '$') + '</span>';
                    const total = fila.insertCell();
                    total.className = 'text-right whitespace-nowrap';
                    total.textContent = usd(v.total);
                    const estadoCelda = fila.insertCell();
                    estadoCelda.className = 'text-center';
                    estadoCelda.innerHTML = v.anulada ? '<span class="insignia-roja">En la papelera</span>' : '<span class="insignia-verde">Vigente</span>';
                });
                const select = campo(c, 'venta');
                const elegida = select.value;
                select.innerHTML = '<option value="">Elige la venta…</option>';
                e.ventas.filter(v => !v.anulada).forEach(v => {
                    select.add(new Option('#' + v.n + ' · ' + e.productos[v.producto].nombre + ' x' + cantidad(v.cantidad) + ' · ' + usd(v.total), String(v.n)));
                });
                select.value = [...select.options].some(o => o.value === elegida) ? elegida : '';
            },
        },
        'cierre': {
            practica: 'Escribe cuánto efectivo contaste y presiona «Cerrar caja». Prueba con montos distintos al esperado.',
            guion: [
                ['decir', 'Fin del día. El sistema ya sabe cuánto efectivo debería haber en el cajón.'],
                ['ver', 'resumen', 'Debería haber = fondo + ventas de contado + abonos. La venta fiada y la eliminada no cuentan.'],
                ['escribir', 'realUsd', '25', 'Cuenta los dólares del cajón y escribe cuánto hay.'],
                ['escribir', 'realVes', e => resumenCaja(e).esperadoVes.toFixed(2), 'Y los bolívares.'],
                ['escribir', 'observaciones', 'Faltó $1 por un vuelto mal dado', 'Si falta o sobra, explícalo aquí.'],
                ['pulsar', 'cerrar', 'Presiona «Cerrar caja».'],
                ['decir', 'Faltó $1.00 y quedó anotado. Con la caja cerrada ya no se vende ese día; si se cerró por error, el Encargado puede reabrirla.'],
            ],
            acciones: {
                cerrar(e, v) {
                    if (e.caja === 'cerrada') return resultado(false, 'Esta caja ya fue cerrada.');
                    if (e.caja === 'sin-abrir') return resultado(false, 'Primero abre la caja del día.');
                    const realUsd = numero(v.realUsd);
                    const realVes = v.realVes.trim() === '' ? 0 : numero(v.realVes);
                    if (isNaN(realUsd)) return resultado(false, 'Escribe cuántos dólares contaste en el cajón (puede ser 0).');
                    if (isNaN(realVes)) return resultado(false, 'Escribe los bolívares solo con números, por ejemplo 500 o 500,50.');
                    const r = resumenCaja(e);
                    e.cierre = { difUsd: redondear(realUsd - r.esperadoUsd), difVes: redondear(realVes - r.esperadoVes) };
                    e.caja = 'cerrada';
                    return resultado(true, 'Caja cerrada. Dólares: ' + diferenciaTexto(e.cierre.difUsd, usd) + '. Bolívares: ' + diferenciaTexto(e.cierre.difVes, ves) + '.');
                },
            },
            pintar(e, c) {
                const r = resumenCaja(e);
                salida(c, 'estado-caja').innerHTML = insigniaCaja(e.caja);
                salida(c, 'fondo-usd').textContent = usd(e.fondo.usd);
                salida(c, 'fondo-ves').textContent = ves(e.fondo.ves);
                salida(c, 'contado-usd').textContent = usd(r.contadoUsd);
                salida(c, 'contado-ves').textContent = ves(r.contadoVes);
                salida(c, 'abonos-usd').textContent = usd(r.abonosUsd);
                salida(c, 'abonos-ves').textContent = ves(r.abonosVes);
                salida(c, 'esperado-usd').textContent = usd(r.esperadoUsd);
                salida(c, 'esperado-ves').textContent = ves(r.esperadoVes);
            },
        },
        'revisar': {
            guion: [
                ['decir', 'Con todo lo del día, el sistema arma los reportes.'],
                ['ver', 'kardex', 'El kardex del tomate: cada entrada y salida con lo que queda. La venta eliminada no aparece.'],
                ['ver', 'ganancia', 'Ganancia = lo vendido menos lo que costó. El tomate costó $1.20 el kg.'],
                ['ver', 'sin-costo', 'El aguacate no tiene compras registradas: su venta no entra en la ganancia y se avisa aparte.'],
                ['ver', 'lugares', 'Esto lo encuentras en Reportes, en Historial y en el kardex de cada producto.'],
                ['decir', 'Fin del recorrido. Al otro día se empieza otra vez abriendo la caja.'],
            ],
            pintar(e, c) {
                const anuladas = new Set(e.ventas.filter(v => v.anulada).map(v => v.n));
                let queda = 0;
                const kardex = salida(c, 'kardex');
                kardex.innerHTML = '';
                e.movimientos.filter(m => m.producto === 'tomate' && !anuladas.has(m.venta)).forEach(m => {
                    queda = redondear(queda + m.cantidad, 3);
                    const fila = kardex.insertRow();
                    fila.insertCell().textContent = m.concepto;
                    const mov = fila.insertCell();
                    mov.className = 'text-right whitespace-nowrap ' + (m.cantidad < 0 ? 'text-rose-700 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400');
                    mov.textContent = (m.cantidad < 0 ? '−' : '+') + cantidad(Math.abs(m.cantidad)) + ' kg';
                    const saldo = fila.insertCell();
                    saldo.className = 'text-right whitespace-nowrap font-semibold';
                    saldo.textContent = cantidad(queda) + ' kg';
                });

                const ganancia = salida(c, 'ganancia');
                ganancia.innerHTML = '';
                const sinCosto = [];
                Object.entries(e.productos).forEach(([clave, p]) => {
                    const vendidas = e.ventas.filter(v => !v.anulada && v.producto === clave);
                    if (!vendidas.length) return;
                    const unidades = vendidas.reduce((s, v) => s + v.cantidad, 0);
                    const vendido = vendidas.reduce((s, v) => s + v.total, 0);
                    const costo = costoPromedio(e, clave);
                    if (costo === null) { sinCosto.push(p.nombre.toLowerCase() + ' (' + usd(vendido) + ')'); return; }
                    const fila = ganancia.insertRow();
                    fila.insertCell().textContent = p.nombre + ' x' + cantidad(unidades);
                    [vendido, costo * unidades, vendido - costo * unidades].forEach((monto, i) => {
                        const celda = fila.insertCell();
                        celda.className = 'text-right whitespace-nowrap' + (i === 2 ? ' font-semibold text-emerald-700 dark:text-emerald-400' : '');
                        celda.textContent = usd(monto);
                    });
                });
                if (!ganancia.rows.length) {
                    const celda = ganancia.insertRow().insertCell();
                    celda.colSpan = 4;
                    celda.className = 'tabla-vacia';
                    celda.textContent = 'Todavía no hay ventas de productos con costo conocido.';
                }
                const zonaSinCosto = c.querySelector('[data-zona="sin-costo"]');
                zonaSinCosto.hidden = !sinCosto.length;
                salida(c, 'sin-costo').textContent = 'Sin costo conocido: ' + sinCosto.join(', ') + '. No entra en la ganancia hasta que se registre una compra de ese producto.';
            },
        },
    };

    function pintarAyudaCantidad(e, c) {
        const p = e.productos[campo(c, 'producto').value];
        salida(c, 'ayuda-cantidad').textContent = !p
            ? 'Elige primero el producto: de él depende si la cantidad acepta decimales.'
            : p.entero
                ? 'Se cuenta por unidad: sin decimales. Hay ' + cantidad(p.stock) + '.'
                : 'Por kg: hasta 3 decimales. Hay ' + cantidad(p.stock) + ' kg.';
    }

    function pintarVenta(e, c) {
        const credito = campo(c, 'tipoPago').value === 'credito';
        c.querySelector('[data-solo="contado"]').hidden = credito;
        c.querySelector('[data-solo="credito"]').hidden = !credito;
        const disponible = Math.max(e.cliente.limite - e.cliente.saldo, 0);
        salida(c, 'credito').textContent = campo(c, 'cliente').value
            ? 'Debe ' + usd(e.cliente.saldo) + ' de un límite de ' + usd(e.cliente.limite) + '. Se le puede fiar hasta ' + usd(disponible) + '.'
            : '';
        pintarAyudaCantidad(e, c);
        const p = e.productos[campo(c, 'producto').value];
        const n = numero(campo(c, 'cantidad').value);
        salida(c, 'total').textContent = p && n > 0 ? usd(p.precio * n) + ' · ' + ves(redondear(p.precio * n) * TASA) : '—';
    }

    const insigniaCaja = estado => ({
        'sin-abrir': '<span class="insignia-ambar">Sin abrir</span>',
        'abierta': '<span class="insignia-verde">Abierta</span>',
        'cerrada': '<span class="insignia-gris">Cerrada</span>',
    })[estado];

    const diferenciaTexto = (dif, formato) => Math.abs(dif) < 0.005 ? 'cuadra exacto' : dif < 0 ? 'faltan ' + formato(-dif) : 'sobran ' + formato(dif);

    function correrSinAnimacion(e, id) {
        const cap = CAPITULOS[id];
        if (cap.preparar) cap.preparar(e);
        const valores = {};
        cap.guion.forEach(([tipo, a, b]) => {
            if (tipo === 'escribir' || tipo === 'elegir') valores[a] = resolver(b, e);
            if (tipo === 'pulsar') cap.acciones[a](e, { ...valores });
        });
    }

    function estadoAlEmpezar(indice) {
        const e = estadoBase();
        for (let i = 0; i < indice; i++) correrSinAnimacion(e, ids[i]);
        const cap = CAPITULOS[ids[indice]];
        if (cap.preparar) cap.preparar(e);
        return e;
    }

    const resolver = (valor, e) => (typeof valor === 'function' ? valor(e) : valor);

    let actual = 0;
    let estado = estadoBase();
    let modo = 'viendo'; // viendo | fin | practica
    let ejecucion = 0;
    let pausado = false;

    const contenedor = () => escenario.querySelector('[data-capitulo="' + ids[actual] + '"]');
    const campo = (c, nombre) => c.querySelector('[data-campo="' + nombre + '"]');
    const salida = (c, nombre) => c.querySelector('[data-salida="' + nombre + '"]');
    const zona = (c, nombre) => c.querySelector('[data-zona="' + nombre + '"]');

    function leerCampos(c) {
        const valores = {};
        c.querySelectorAll('[data-campo]').forEach(el => { valores[el.dataset.campo] = el.value; });
        return valores;
    }

    function limpiarCampos(c) {
        c.querySelectorAll('input[data-campo]').forEach(el => { el.value = ''; });
        c.querySelectorAll('select[data-campo]').forEach(el => { el.selectedIndex = 0; });
        const mensaje = c.querySelector('[data-mensaje]');
        if (mensaje) mensaje.hidden = true;
    }

    // destacar: marca por un momento las cifras que cambiaron por una acción (no al cambiar de capítulo)
    function pintarEstado(destacar) {
        const r = resumenCaja(estado);
        const textos = {
            caja: insigniaCaja(estado.caja),
            // En el celular los bolívares van en la misma línea que los dólares
            efectivo: estado.caja === 'sin-abrir' ? '—' : usd(r.esperadoUsd) + '<span class="sm:block"><span class="sm:hidden"> · </span>' + ves(r.esperadoVes) + '</span>',
            tomate: cantidad(estado.productos.tomate.stock) + ' kg',
            aguacate: cantidad(estado.productos.aguacate.stock) + ' unidades',
            saldo: usd(estado.cliente.saldo),
        };
        Object.entries(textos).forEach(([clave, html]) => {
            const el = document.querySelector('[data-estado="' + clave + '"]');
            if (el.innerHTML === html) return;
            el.innerHTML = html;
            if (destacar) {
                el.parentElement.classList.remove('guia-cambio');
                void el.parentElement.offsetWidth;
                el.parentElement.classList.add('guia-cambio');
            }
        });
    }

    function pintarTodo(destacar = false) {
        const cap = CAPITULOS[ids[actual]];
        if (cap.pintar) cap.pintar(estado, contenedor());
        pintarEstado(destacar);
    }

    function mostrarMensaje(c, res) {
        const caja = c.querySelector('[data-mensaje]');
        caja.className = res.ok ? 'aviso-azul' : 'aviso-rojo';
        caja.innerHTML = document.getElementById(res.ok ? 'guia-icono-ok' : 'guia-icono-error').innerHTML;
        const texto = document.createElement('p');
        texto.textContent = res.texto;
        caja.appendChild(texto);
        caja.hidden = false;
    }

    function ejecutarAccion(nombre) {
        const c = contenedor();
        const res = CAPITULOS[ids[actual]].acciones[nombre](estado, leerCampos(c));
        mostrarMensaje(c, res);
        pintarTodo(true);
        return res;
    }

    async function esperar(ms, id) {
        let restante = ms;
        while (restante > 0) {
            await new Promise(r => setTimeout(r, 50));
            if (id !== ejecucion) throw CANCELADO;
            if (!pausado) restante -= 50;
        }
        if (id !== ejecucion) throw CANCELADO;
    }

    const tiempoLectura = texto => (texto ? Math.max(1800, texto.length * 55) : 0);

    function narrar(texto) {
        if (texto) narracion.textContent = texto;
    }

    async function moverCursor(el, id) {
        // Solo se desplaza la página si la persona ya está mirando la pantalla de muestra
        // (si bajó a leer la explicación del capítulo, no hay que quitarle lo que está leyendo)
        const visible = escenario.getBoundingClientRect();
        if (visible.top < window.innerHeight && visible.bottom > 0) {
            el.scrollIntoView({ block: 'nearest', behavior: reducido ? 'auto' : 'smooth' });
        }
        if (reducido) return;
        const caja = el.getBoundingClientRect();
        const base = escenario.getBoundingClientRect();
        const x = caja.left - base.left + Math.min(28, caja.width / 2);
        const y = caja.top - base.top + Math.min(caja.height / 2, 24);
        cursor.style.opacity = '1';
        cursor.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
        await esperar(600, id);
    }

    async function ejecutarPaso(paso, id) {
        const [tipo, a, b, c] = paso;
        const cont = contenedor();
        if (tipo === 'decir') {
            narrar(a);
            await esperar(tiempoLectura(a), id);
        } else if (tipo === 'ver') {
            const el = zona(cont, a);
            narrar(b);
            await moverCursor(el, id);
            el.classList.add('guia-resaltado');
            try {
                await esperar(tiempoLectura(b), id);
            } finally {
                el.classList.remove('guia-resaltado');
            }
        } else if (tipo === 'escribir' || tipo === 'elegir') {
            narrar(c);
            const el = campo(cont, a);
            await moverCursor(el, id);
            const valor = String(resolver(b, estado));
            const inicio = Date.now();
            if (tipo === 'elegir') {
                el.value = valor;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                el.value = '';
                for (const letra of valor) {
                    el.value += letra;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    if (!reducido) await esperar(80, id);
                }
            }
            await esperar(Math.max(600, tiempoLectura(c) - (Date.now() - inicio)), id);
        } else if (tipo === 'pulsar') {
            narrar(b);
            const el = cont.querySelector('[data-accion="' + a + '"]');
            await moverCursor(el, id);
            el.classList.add('guia-pulsado');
            await esperar(200, id);
            el.classList.remove('guia-pulsado');
            const res = ejecutarAccion(a);
            await esperar(Math.max(1500, tiempoLectura(b), res.texto.length * 40), id);
        }
    }

    async function reproducir() {
        const id = ++ejecucion;
        pausado = false;
        cambiarModo('viendo');
        try {
            await esperar(400, id);
            for (const paso of CAPITULOS[ids[actual]].guion) await ejecutarPaso(paso, id);
            cursor.style.opacity = '0';
            const siguiente = vecino(1);
            cambiarModo('fin');
            if (casillaSeguir.checked && siguiente !== null) {
                await esperar(2500, id);
                irA(siguiente);
            }
        } catch (error) {
            if (error !== CANCELADO) throw error;
        }
    }

    const visibles = () => ids.map((_, i) => i).filter(i => !casillaSoloMios || !casillaSoloMios.checked
        || document.querySelectorAll('[data-capitulo-item]')[i].dataset.quien === 'todos');

    function vecino(paso) {
        const lista = visibles();
        const posicion = lista.indexOf(actual);
        const destino = posicion === -1 ? lista.find(i => (paso > 0 ? i > actual : i < actual)) : lista[posicion + paso];
        return destino === undefined ? null : destino;
    }

    function cambiarModo(nuevo) {
        modo = nuevo;
        contenedor().inert = modo !== 'practica';
        const tienePractica = !!CAPITULOS[ids[actual]].acciones;
        botonPracticar.disabled = !tienePractica || modo === 'practica';
        botonPausa.disabled = modo !== 'viendo';
        botonPausa.querySelector('svg').outerHTML = document.getElementById(pausado ? 'guia-icono-seguir' : 'guia-icono-pausa').innerHTML;
        botonPausa.querySelector('span').textContent = pausado ? 'Seguir' : 'Pausa';
        document.getElementById('guia-anterior').disabled = vecino(-1) === null;
        document.getElementById('guia-siguiente').disabled = vecino(1) === null;
        if (modo === 'practica') {
            cursor.style.opacity = '0';
            pista.textContent = 'Estás practicando. Para volver a ver la animación, presiona «Repetir».';
        } else if (modo === 'fin') {
            pista.textContent = tienePractica ? 'Presiona «Practicar» para hacerlo tú, o «Siguiente» para seguir.' : 'Presiona «Siguiente» para seguir.';
        } else {
            pista.textContent = tienePractica ? 'Puedes pausar la animación o presionar «Practicar» para hacerlo tú.' : '';
        }
    }

    function irA(indice) {
        ejecucion++;
        cursor.style.opacity = '0';
        actual = indice;
        const id = ids[actual];
        escenario.querySelectorAll('[data-capitulo]').forEach(el => { el.hidden = el.dataset.capitulo !== id; });
        document.querySelectorAll('[data-texto]').forEach(el => { el.hidden = el.dataset.texto !== id; });
        document.getElementById('guia-pantalla').textContent = contenedor().dataset.pantalla;
        document.querySelectorAll('[data-ir]').forEach(boton => {
            const activo = parseInt(boton.dataset.ir, 10) === actual;
            boton.classList.toggle('btn-primario', activo);
            boton.classList.toggle('btn-secundario', !activo);
            if (activo) boton.setAttribute('aria-current', 'step'); else boton.removeAttribute('aria-current');
            if (activo) {
                // En el celular la fila de capítulos se desliza de lado: centrar el actual sin mover la página
                const fila = document.getElementById('guia-capitulos');
                const b = boton.getBoundingClientRect();
                const f = fila.getBoundingClientRect();
                fila.scrollLeft += (b.left + b.width / 2) - (f.left + f.width / 2);
            }
        });
        history.replaceState(null, '', '#capitulo-' + (actual + 1));
        estado = estadoAlEmpezar(actual);
        limpiarCampos(contenedor());
        narracion.textContent = '';
        pintarTodo();
        reproducir();
    }

    function practicar() {
        ejecucion++;
        estado = estadoAlEmpezar(actual);
        const c = contenedor();
        limpiarCampos(c);
        pintarTodo();
        narrar(CAPITULOS[ids[actual]].practica);
        cambiarModo('practica');
        const primero = c.querySelector('[data-campo]');
        if (primero) primero.focus({ preventScroll: true });
    }

    function aplicarFiltroRol() {
        document.querySelectorAll('[data-capitulo-item]').forEach((li, i) => { li.hidden = !visibles().includes(i); });
        if (!visibles().includes(actual)) irA(vecino(1) ?? visibles()[0]);
        else cambiarModo(modo);
    }

    escenario.addEventListener('input', () => pintarTodo());
    escenario.addEventListener('change', () => pintarTodo());
    escenario.addEventListener('click', evento => {
        const boton = evento.target.closest('[data-accion]');
        if (boton && modo === 'practica') ejecutarAccion(boton.dataset.accion);
    });
    escenario.addEventListener('keydown', evento => {
        if (evento.key !== 'Enter' || modo !== 'practica' || evento.target.tagName !== 'INPUT') return;
        const boton = contenedor().querySelector('[data-accion]');
        if (boton) ejecutarAccion(boton.dataset.accion);
    });

    document.querySelectorAll('[data-ir]').forEach(boton => boton.addEventListener('click', () => irA(parseInt(boton.dataset.ir, 10))));
    document.getElementById('guia-anterior').addEventListener('click', () => { const i = vecino(-1); if (i !== null) irA(i); });
    document.getElementById('guia-siguiente').addEventListener('click', () => { const i = vecino(1); if (i !== null) irA(i); });
    document.getElementById('guia-repetir').addEventListener('click', () => irA(actual));
    botonPracticar.addEventListener('click', practicar);
    botonPausa.addEventListener('click', () => { pausado = !pausado; cambiarModo(modo); });
    if (casillaSoloMios) casillaSoloMios.addEventListener('change', aplicarFiltroRol);

    document.addEventListener('keydown', evento => {
        if (evento.target.closest('input, select, textarea') || evento.altKey || evento.ctrlKey || evento.metaKey) return;
        const destino = evento.key === 'ArrowRight' ? vecino(1) : evento.key === 'ArrowLeft' ? vecino(-1) : null;
        if (destino !== null) irA(destino);
    });

    if (reducido) casillaSeguir.checked = false;
    const pedido = parseInt((location.hash.match(/^#capitulo-(\d+)$/) || [])[1], 10) - 1;
    document.querySelectorAll('[data-capitulo-item]').forEach((li, i) => { li.hidden = !visibles().includes(i); });
    irA(visibles().includes(pedido) ? pedido : visibles()[0]);
})();
</script>
