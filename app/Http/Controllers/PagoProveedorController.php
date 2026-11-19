<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\PagoProveedor;
use App\Models\Proveedor;
use App\Services\CajaService;
use App\Support\Moneda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pagos a proveedores por compras a crédito. Bajan lo que se le debe; si se pagan con el efectivo
 * de la caja, se restan en el cuadre del día como los gastos.
 */
class PagoProveedorController extends Controller
{
    public function store(Request $request, Proveedor $proveedor, CajaService $cajas)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01|max:9999999999',
            'moneda' => 'required|in:USD,VES',
            'origen' => 'required|in:caja,otro',
            'notas' => 'nullable|string|max:500',
        ], [
            'origen.required' => 'Indica de dónde salió el dinero: de la caja o de otra cuenta.',
        ]);

        $moneda = $request->moneda;
        $tasa = Moneda::tasa();
        if ($moneda === 'VES' && $tasa <= 0) {
            return back()->withErrors(['error' => 'No hay tasa del BCV cargada, no se puede registrar un pago en bolívares.'])->withInput();
        }

        $montoMoneda = round((float) $request->monto, 2);
        $montoUsd = $moneda === 'VES' ? Moneda::aUsd($montoMoneda, $tasa) : $montoMoneda;
        $deCaja = $request->origen === 'caja';

        if ($deCaja && ($aviso = CierreCaja::avisoCajaPendiente())) {
            return back()->withErrors(['error' => $aviso])->withInput();
        }

        $error = DB::transaction(function () use ($proveedor, $cajas, $moneda, $tasa, $montoMoneda, $montoUsd, $deCaja, $request) {
            // Se bloquea el proveedor para que dos pagos a la vez no pasen de lo que se debe
            $proveedor = Proveedor::lockForUpdate()->findOrFail($proveedor->id);
            $saldo = $proveedor->estadoDeuda()['saldo'];
            if ($montoUsd > $saldo + 0.01) {
                return 'El pago (' . Moneda::usd($montoUsd) . ') es mayor que lo que se le debe a este proveedor (' . Moneda::usd($saldo) . ').';
            }

            $caja = null;
            if ($deCaja) {
                $caja = CierreCaja::whereDate('fecha', now()->toDateString())->lockForUpdate()->first();
                if (!$caja || $caja->estado !== 'ABIERTA') {
                    return 'Para pagar con el efectivo de la caja, la caja de hoy tiene que estar abierta. Si pagaste por transferencia, elige "Otra cuenta".';
                }
                $disponible = $cajas->esperado($caja, $cajas->resumen($caja->fecha))[$moneda === 'VES' ? 'ves' : 'usd'];
                if ($montoMoneda > $disponible + 0.001) {
                    $texto = $moneda === 'VES' ? Moneda::ves($disponible) : Moneda::usd($disponible);
                    return "En la caja no hay tanto efectivo en esa moneda ({$texto}). Revisa el monto o elige \"Otra cuenta\".";
                }
            }

            PagoProveedor::create([
                'proveedor_id' => $proveedor->id,
                'user_id' => auth()->id(),
                'cierre_caja_id' => $caja?->id,
                'monto' => min($montoUsd, $saldo),
                'moneda' => $moneda,
                'monto_moneda' => $montoMoneda,
                'tasa_cambio' => $tasa > 0 ? $tasa : null,
                'notas' => $request->notas,
                'fecha' => now(),
            ]);

            return null;
        });

        if ($error) {
            return back()->withErrors(['error' => $error])->withInput();
        }

        return redirect()->route('proveedores.show', $proveedor)->with('success', 'Pago registrado. La deuda con el proveedor se actualizó.');
    }
}
