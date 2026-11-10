<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Support\Contacto;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::withCount('compras')->latest()->get();
        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    // Quita guiones/espacios de los teléfonos y valida: solo números, 11 dígitos empezando por 0.
    private function validarProveedor(Request $request, array $extra = []): array
    {
        $request->merge([
            'telefono' => Contacto::limpiarTelefono($request->input('telefono')),
            'whatsapp' => Contacto::limpiarTelefono($request->input('whatsapp')),
        ]);

        return $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => Contacto::REGLA_TELEFONO,
            'whatsapp' => Contacto::REGLA_TELEFONO,
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'notas' => 'nullable|string|max:500',
        ] + $extra);
    }

    public function store(Request $request)
    {
        $datos = $this->validarProveedor($request);

        Proveedor::create($datos);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado exitosamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $this->validarProveedor($request, [
            'activo' => 'nullable|boolean',
        ]);

        $proveedor->update([
            'nombre' => $request->nombre,
            'contacto' => $request->contacto,
            'telefono' => $request->telefono,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email,
            'direccion' => $request->direccion,
            'notas' => $request->notas,
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->compras()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar: este proveedor tiene compras registradas. Puedes marcarlo como inactivo en su lugar.']);
        }

        $proveedor->delete();
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado.');
    }

    /**
     * Activa o desactiva un proveedor desde la tabla. Es la forma de "quitarlo" cuando
     * ya tiene compras (no se puede borrar): deja de aparecer para compras nuevas.
     */
    public function alternarActivo(Proveedor $proveedor)
    {
        $proveedor->update(['activo' => !$proveedor->activo]);

        return back()->with('success', $proveedor->activo
            ? "{$proveedor->nombre} vuelve a estar disponible para compras."
            : "{$proveedor->nombre} quedó inactivo: no aparecerá en compras nuevas. Su historial se conserva.");
    }

    // Muestra la ficha del proveedor con su historial de compras
    public function show(Proveedor $proveedor)
    {
        $compras = $proveedor->compras()->with(['producto', 'usuario'])->latest('fecha')->get();
        $totalComprado = $proveedor->totalComprado();
        $ultimaCompra = $proveedor->ultimaCompra();

        return view('proveedores.show', compact('proveedor', 'compras', 'totalComprado', 'ultimaCompra'));
    }
}
