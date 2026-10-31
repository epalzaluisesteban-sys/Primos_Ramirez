<?php

namespace App\Http\Controllers;

use App\Support\Almacen;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        // Obtiene las categorías junto con la cantidad de productos asociados
        $categorias = Categoria::withCount('productos')->latest()->get();
        return view('categorias.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string|max:255',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $datos = $request->except('imagen');
        if ($request->hasFile('imagen')) {
            $datos['imagen'] = Almacen::guardar($request->file('imagen'), 'categorias');
        }

        Categoria::create($datos);

        return redirect()->route('categorias.index')->with('success', 'Categoría creada exitosamente.');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre,' . $categoria->id,
            'descripcion' => 'nullable|string|max:255',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $datos = [
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ];

        if ($request->hasFile('imagen')) {
            if ($categoria->imagen) {
                Almacen::borrar($categoria->imagen);
            }
            $datos['imagen'] = Almacen::guardar($request->file('imagen'), 'categorias');
        }

        $categoria->update($datos);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Categoria $categoria)
    {
        // Validación de seguridad para no dejar productos huérfanos
        if ($categoria->productos()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar la categoría porque tiene productos vinculados.']);
        }

        if ($categoria->imagen) {
            Almacen::borrar($categoria->imagen);
        }

        $categoria->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada correctamente.');
    }
}
