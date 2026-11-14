<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\MermaController;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\TasaCambioController;
use App\Http\Controllers\RespaldoController;

// ==========================================
// Rutas Públicas (Autenticación)
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// Rutas Protegidas (Requieren Iniciar Sesión)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // --- Acceso General (Dueño, Encargado, Obrero) ---
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Módulo de Caja
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
    Route::put('/caja/{caja}/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::get('caja-exportar-excel', [CajaController::class, 'exportarExcel'])->name('caja.excel');
    Route::get('caja-exportar-pdf', [CajaController::class, 'exportarPDF'])->name('caja.pdf');

    // Módulo de Ventas y Reportes
    Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store']);
    Route::get('ventas/{venta}/pdf', [VentaController::class, 'descargarPDF'])->name('ventas.pdf');
    Route::get('ventas/{venta}/ticket', [VentaController::class, 'ticket'])->name('ventas.ticket');
    Route::get('ventas-exportar-excel', [VentaController::class, 'exportarExcel'])->name('ventas.excel');
    Route::get('ventas/{venta}', [VentaController::class, 'show'])->whereNumber('venta')->name('ventas.show');

    // Registrar un cliente sin salir de la venta (nombre, teléfono y cédula; el límite de crédito se pone en Clientes)
    Route::post('clientes-rapido', [ClienteController::class, 'registroRapido'])->name('clientes.rapido');

    // Clientes para todos: el obrero entra a la lista y a la ficha para cobrar fiados.
    // Crear, editar y exportar siguen siendo de encargado o dueño (whereNumber: que "clientes/create" no caiga aquí).
    Route::get('clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('clientes/{cliente}', [ClienteController::class, 'show'])->whereNumber('cliente')->name('clientes.show');
    Route::post('clientes/{cliente}/abonos', [ClienteController::class, 'registrarAbono'])->name('clientes.abonos.store');

    // Módulo de Perfil de Usuario
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

    // Guía "Cómo usar": recorrido de práctica, solo lectura (no tiene formularios que envíen datos)
    Route::view('/ayuda', 'ayuda.index')->name('ayuda.index');


    // --- Acceso Intermedio (Dueño y Encargado) ---
    Route::middleware(['role:Dueno,Encargado'])->group(function () {
        
        // Gestión de Productos e Historial Kardex
        Route::resource('productos', ProductoController::class)->except(['show', 'destroy']);
        Route::get('productos-exportar-excel', [ProductoController::class, 'exportarExcel'])->name('productos.excel');
        Route::get('productos-exportar-pdf', [ProductoController::class, 'exportarPDF'])->name('productos.pdf');
        Route::get('productos/{producto}/kardex', [ProductoController::class, 'kardex'])->name('productos.kardex');
        Route::get('productos/{producto}/kardex/excel', [ProductoController::class, 'kardexExcel'])->name('productos.kardex.excel');
        Route::get('productos/{producto}/kardex/pdf', [ProductoController::class, 'kardexPDF'])->name('productos.kardex.pdf');

        // Gestión de Categorías
        Route::resource('categorias', CategoriaController::class)->except(['show', 'destroy']);

        // Entrada de Inventario (Compras)
        Route::resource('compras', CompraController::class)->only(['index', 'store']);
        Route::get('compras-exportar-excel', [CompraController::class, 'exportarExcel'])->name('compras.excel');
        Route::get('compras-exportar-pdf', [CompraController::class, 'exportarPDF'])->name('compras.pdf');

        // Reabrir la caja de hoy si se cerró por error (queda anotado en sus observaciones)
        Route::put('/caja/{caja}/reabrir', [CajaController::class, 'reabrir'])->name('caja.reabrir');

        // Editar o mandar a la papelera ventas de hoy (con la caja abierta), y restaurarlas
        Route::get('ventas/{venta}/editar', [VentaController::class, 'edit'])->name('ventas.edit');
        Route::put('ventas/{venta}', [VentaController::class, 'update'])->name('ventas.update');
        Route::put('ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
        Route::get('ventas-papelera', [VentaController::class, 'papelera'])->name('ventas.papelera');
        Route::put('ventas/{venta}/restaurar', [VentaController::class, 'restaurar'])->name('ventas.restaurar');

        // Gestión de Proveedores
        Route::patch('proveedores/{proveedor}/activo', [ProveedorController::class, 'alternarActivo'])->name('proveedores.activo');
        Route::resource('proveedores', ProveedorController::class)
            ->except(['destroy'])
            ->parameters(['proveedores' => 'proveedor']);

        // Clientes y Cuentas por Cobrar (Fiado)
        Route::resource('clientes', ClienteController::class)
            ->except(['index', 'show', 'destroy'])
            ->parameters(['clientes' => 'cliente']);
        Route::get('clientes-exportar-excel', [ClienteController::class, 'exportarExcel'])->name('clientes.excel');
        Route::get('clientes-exportar-pdf', [ClienteController::class, 'exportarPDF'])->name('clientes.pdf');

        // Control de Mermas / Pérdidas de Inventario
        Route::resource('mermas', MermaController::class)->only(['index', 'store']);
        Route::get('mermas-exportar-excel', [MermaController::class, 'exportarExcel'])->name('mermas.excel');
        Route::get('mermas-exportar-pdf', [MermaController::class, 'exportarPDF'])->name('mermas.pdf');

        // Reportes Estadísticos (Ganancias y Comparativos)
        Route::get('reportes/ganancias', [ReporteController::class, 'ganancias'])->name('reportes.ganancias');
        Route::get('reportes/ganancias/excel', [ReporteController::class, 'exportarExcel'])->name('reportes.ganancias.excel');
        Route::get('reportes/ganancias/pdf', [ReporteController::class, 'exportarPDF'])->name('reportes.ganancias.pdf');

        // Panel de Estadísticas Avanzado (tendencias, comparativos, predicción de demanda)
        Route::get('reportes/estadisticas', [EstadisticaController::class, 'index'])->name('estadisticas.index');

        // Las alertas de stock ahora son filtros de Productos; el enlace viejo lleva al filtro "Con alerta"
        Route::get('inventario/alertas', fn () => redirect()->route('productos.index', ['filtro' => 'alertas']));

        // Tasa de cambio oficial del BCV (Bs. por USD)
        Route::get('tasa', [TasaCambioController::class, 'index'])->name('tasa.index');
        Route::post('tasa/actualizar', [TasaCambioController::class, 'actualizar'])->name('tasa.actualizar');
        Route::post('tasa', [TasaCambioController::class, 'store'])->name('tasa.store');

        // Historial General (bitácora de toda la actividad del sistema)
        Route::get('historial', [HistorialController::class, 'index'])->name('historial.index');
        Route::get('historial-exportar-excel', [HistorialController::class, 'exportarExcel'])->name('historial.excel');
        Route::get('historial-exportar-pdf', [HistorialController::class, 'exportarPDF'])->name('historial.pdf');
    });


    // --- Acceso Máximo (Solo Dueño) ---
    Route::middleware(['role:Dueno'])->group(function () {
        
        // Eliminación de Registros Críticos
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');
        Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
        Route::delete('proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
        Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        
        // Gestión de Personal / Usuarios
        Route::patch('usuarios/{usuario}/activo', [UserController::class, 'alternarActivo'])->name('usuarios.activo');
        Route::resource('usuarios', UserController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);

        // Respaldo y restauración de toda la información
        Route::get('respaldos', [RespaldoController::class, 'index'])->name('respaldos.index');
        Route::post('respaldos', [RespaldoController::class, 'store'])->name('respaldos.store');
        Route::get('respaldos/{respaldo}/descargar', [RespaldoController::class, 'descargar'])->name('respaldos.descargar');
        Route::delete('respaldos/{respaldo}', [RespaldoController::class, 'destroy'])->name('respaldos.destroy');
        Route::post('respaldos/restaurar', [RespaldoController::class, 'preparar'])->name('respaldos.preparar');
        Route::get('respaldos/restaurar/{pendiente}', [RespaldoController::class, 'confirmar'])->whereUuid('pendiente')->name('respaldos.confirmar');
        Route::post('respaldos/restaurar/{pendiente}', [RespaldoController::class, 'restaurar'])->whereUuid('pendiente')->name('respaldos.restaurar');
    });

});