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
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\TasaCambioController;

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

    // Módulo de Perfil de Usuario
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');


    // --- Acceso Intermedio (Dueño y Encargado) ---
    Route::middleware(['role:Dueno,Encargado'])->group(function () {
        
        // Gestión de Productos e Historial Kardex
        Route::resource('productos', ProductoController::class)->except(['destroy']);
        Route::get('productos-exportar-excel', [ProductoController::class, 'exportarExcel'])->name('productos.excel');
        Route::get('productos-exportar-pdf', [ProductoController::class, 'exportarPDF'])->name('productos.pdf');
        Route::get('productos/{producto}/kardex', [ProductoController::class, 'kardex'])->name('productos.kardex');
        Route::get('productos/{producto}/kardex/excel', [ProductoController::class, 'kardexExcel'])->name('productos.kardex.excel');
        Route::get('productos/{producto}/kardex/pdf', [ProductoController::class, 'kardexPDF'])->name('productos.kardex.pdf');

        // Gestión de Categorías
        Route::resource('categorias', CategoriaController::class)->except(['destroy']);

        // Entrada de Inventario (Compras)
        Route::resource('compras', CompraController::class)->only(['index', 'store']);
        Route::get('compras-exportar-excel', [CompraController::class, 'exportarExcel'])->name('compras.excel');
        Route::get('compras-exportar-pdf', [CompraController::class, 'exportarPDF'])->name('compras.pdf');

        // Edición y anulación de ventas recientes (mismo día, no anuladas)
        Route::get('ventas/{venta}/editar', [VentaController::class, 'edit'])->name('ventas.edit');
        Route::put('ventas/{venta}', [VentaController::class, 'update'])->name('ventas.update');
        Route::put('ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');

        // Gestión de Proveedores
        Route::resource('proveedores', ProveedorController::class)
            ->except(['destroy'])
            ->parameters(['proveedores' => 'proveedor']);

        // Clientes y Cuentas por Cobrar (Fiado)
        Route::resource('clientes', ClienteController::class)
            ->except(['destroy'])
            ->parameters(['clientes' => 'cliente']);
        Route::post('clientes/{cliente}/abonos', [ClienteController::class, 'registrarAbono'])->name('clientes.abonos.store');
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

        // Inventario Inteligente (alertas de stock, vencimiento y sugerencias de compra)
        Route::get('inventario/alertas', [InventarioController::class, 'alertas'])->name('inventario.alertas');

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
        Route::resource('usuarios', UserController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    });

});