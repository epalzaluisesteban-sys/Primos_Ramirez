<?php

namespace App\Http\Controllers;

use App\Exceptions\RespaldoInvalido;
use App\Models\Respaldo;
use App\Services\RespaldoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Administración → Respaldo y restauración (solo Dueño). */
class RespaldoController extends Controller
{
    // Subidos sin confirmar. La extensión da igual: el formato (.json o .tar.gz antiguo) se reconoce por el contenido.
    private const PENDIENTES = RespaldoService::CARPETA . '/pendientes';

    public function __construct(private RespaldoService $servicio)
    {
    }

    public function index()
    {
        $this->limpiarPendientes();

        $respaldos = Respaldo::latest('id')->take(30)->get();
        $ultimoManual = Respaldo::where('tipo', 'manual')->latest('id')->first();
        $diasSinRespaldo = $ultimoManual
            ? (int) $ultimoManual->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
            : null;
        $actual = $this->servicio->resumenActual();
        // Una sola consulta al disco (en Supabase cada consulta es una petición por internet)
        $enServidor = collect(Storage::disk(RespaldoService::disco())->files(RespaldoService::CARPETA))
            ->map(fn ($ruta) => basename($ruta))
            ->flip();

        return view('respaldos.index', compact('respaldos', 'ultimoManual', 'diasSinRespaldo', 'actual', 'enServidor'));
    }

    public function store()
    {
        try {
            $respaldo = $this->servicio->crear(auth()->user()->name);
        } catch (RespaldoInvalido $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        // La página recarga con el historial al día y la descarga arranca sola
        return redirect()->route('respaldos.index')
            ->with('success', 'Respaldo creado. Guarda el archivo descargado fuera de esta computadora.')
            ->with('descargar', $respaldo->id);
    }

    public function descargar(Respaldo $respaldo)
    {
        if (!$respaldo->existeArchivo()) {
            return back()->withErrors(['error' => 'Ese archivo ya no está en el servidor. Usa la copia que descargaste.']);
        }

        return Storage::disk(RespaldoService::disco())->download($respaldo->ruta(), $respaldo->archivo);
    }

    public function destroy(Respaldo $respaldo)
    {
        Storage::disk(RespaldoService::disco())->delete($respaldo->ruta());
        $respaldo->delete();

        return back()->with('success', 'Respaldo eliminado del servidor.');
    }

    // Paso 1: subir el archivo. Se revisa y se guarda aparte; nada cambia hasta confirmar.
    public function preparar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:102400'],
        ], [
            'archivo.required' => 'Elige el archivo de respaldo (.json).',
            'archivo.max' => 'El archivo pesa más de 100 MB.',
            'archivo.uploaded' => 'No se pudo subir el archivo. Puede que pese más de lo que permite el servidor.',
        ]);

        try {
            $this->servicio->leer($request->file('archivo')->getRealPath());
        } catch (RespaldoInvalido $e) {
            return back()->withErrors(['archivo' => $e->getMessage()]);
        }

        $pendiente = (string) Str::uuid();
        Storage::disk(RespaldoService::DISCO_PENDIENTES)->putFileAs(self::PENDIENTES, $request->file('archivo'), $pendiente . '.respaldo');

        return redirect()->route('respaldos.confirmar', $pendiente);
    }

    // Paso 2: ver qué trae el respaldo comparado con lo actual
    public function confirmar(string $pendiente)
    {
        $ruta = $this->rutaPendiente($pendiente);
        if (!$ruta) {
            return redirect()->route('respaldos.index')->withErrors(['error' => 'El archivo subido ya no está. Vuelve a subirlo.']);
        }

        try {
            $respaldo = $this->servicio->leer($ruta);
        } catch (RespaldoInvalido $e) {
            return redirect()->route('respaldos.index')->withErrors(['error' => $e->getMessage()]);
        }

        $manifiesto = $respaldo['manifiesto'];
        $comparacion = $this->servicio->comparar($respaldo);
        $fecha = $this->servicio->fechaLegible($manifiesto['creado_en'] ?? null);
        // Para saber con qué cuenta entrar después de restaurar
        $duenos = collect($respaldo['datos']['users'])
            ->filter(fn ($u) => ($u['rol'] ?? null) === 'Dueno' && ($u['activo'] ?? 1))
            ->map(fn ($u) => $u['name'] . ' (' . $u['email'] . ')')
            ->values();

        return view('respaldos.confirmar', compact('pendiente', 'manifiesto', 'comparacion', 'fecha', 'duenos'));
    }

    // Paso 3: reemplazar la información
    public function restaurar(Request $request, string $pendiente)
    {
        $request->validate([
            'confirmacion' => ['required', 'string'],
            'password' => ['required', 'current_password'],
        ], [
            'confirmacion.required' => 'Escribe RESTAURAR para confirmar.',
            'password.required' => 'Escribe tu contraseña.',
            'password.current_password' => 'La contraseña no es correcta.',
        ]);

        if (mb_strtoupper(trim($request->confirmacion)) !== 'RESTAURAR') {
            return back()->withErrors(['confirmacion' => 'Escribe la palabra RESTAURAR para confirmar.']);
        }

        $ruta = $this->rutaPendiente($pendiente);
        if (!$ruta) {
            return redirect()->route('respaldos.index')->withErrors(['error' => 'El archivo subido ya no está. Vuelve a subirlo.']);
        }

        try {
            $manifiesto = $this->servicio->leer($ruta)['manifiesto'];
            $this->servicio->restaurar($ruta, auth()->user()->name);
        } catch (RespaldoInvalido $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        Storage::disk(RespaldoService::DISCO_PENDIENTES)->delete(self::PENDIENTES . '/' . $pendiente . '.respaldo');

        // Los usuarios ahora son los del respaldo: se cierran todas las sesiones abiertas
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->delete();
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success',
            'Información restaurada al ' . $this->servicio->fechaLegible($manifiesto['creado_en'] ?? null)
            . '. Entra con un usuario de ese respaldo.');
    }

    private function rutaPendiente(string $pendiente): ?string
    {
        $relativa = self::PENDIENTES . '/' . $pendiente . '.respaldo';
        $disco = Storage::disk(RespaldoService::DISCO_PENDIENTES);

        return $disco->exists($relativa) ? $disco->path($relativa) : null;
    }

    // Archivos subidos que nadie confirmó
    private function limpiarPendientes(): void
    {
        $disco = Storage::disk(RespaldoService::DISCO_PENDIENTES);
        foreach ($disco->files(self::PENDIENTES) as $archivo) {
            if ($disco->lastModified($archivo) < now()->subDay()->getTimestamp()) {
                $disco->delete($archivo);
            }
        }
    }
}
