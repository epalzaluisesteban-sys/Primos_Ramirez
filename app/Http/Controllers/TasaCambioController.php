<?php

namespace App\Http\Controllers;

use App\Models\TasaCambio;
use App\Services\TasaCambioService;
use Illuminate\Http\Request;

class TasaCambioController extends Controller
{
    public function index()
    {
        $historial = TasaCambio::with('usuario')->orderByDesc('fecha')->orderByDesc('id')->take(30)->get();

        return view('tasa.index', ['actual' => TasaCambioService::actual(), 'historial' => $historial]);
    }

    // Consulta el BCV ahora mismo
    public function actualizar(TasaCambioService $servicio)
    {
        $tasa = $servicio->actualizarDesdeApi();

        if (!$tasa) {
            return back()->withErrors(['error' => 'No se pudo consultar el BCV (sin internet o servicio caído). Puedes registrar la tasa manualmente.']);
        }

        return back()->with('success', 'Tasa actualizada desde el BCV: Bs. ' . number_format($tasa->tasa, 4, ',', '.') . ' por $1.');
    }

    // Tasa fijada a mano (por ejemplo, si no hay internet)
    public function store(Request $request, TasaCambioService $servicio)
    {
        $request->validate(['tasa' => 'required|numeric|min:0.0001|max:100000000']);

        $servicio->guardar((float) $request->tasa, 'manual', auth()->id());

        return back()->with('success', 'Tasa manual guardada para hoy.');
    }
}
