<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
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

    private function validacionProveedor(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'notas' => 'nullable|string|max:500',
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->validacionProveedor());

        Proveedor::create($request->all());

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado exitosamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $request->validate($this->validacionProveedor() + [
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

    // Muestra la ficha del proveedor con su historial de compras
    public function show(Proveedor $proveedor)
    {
        $compras = $proveedor->compras()->with(['producto', 'usuario'])->latest('fecha')->get();
        $totalComprado = $proveedor->totalComprado();
        $ultimaCompra = $proveedor->ultimaCompra();

        return view('proveedores.show', compact('proveedor', 'compras', 'totalComprado', 'ultimaCompra'));
    }
}
