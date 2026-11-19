<?php

namespace App\Http\Controllers;

use App\Support\Almacen;
use App\Models\Categoria;
use App\Support\Unicidad;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        // Obtiene las categorías junto con la cantidad de productos asociados
        $categorias = Categoria::withCount('productos')->latest()->get();
        return view('categorias.index', compact('categorias'));
    }

    // "Frutas", "frutas " y "FRUTÁS" son la misma categoría
    private function reglas(?Categoria $categoria = null): array
    {
        return [
            'nombre' => [
                'required', 'string', 'min:2', 'max:100',
                function ($atributo, $valor, $falla) use ($categoria) {
                    if ($otra = Categoria::conClave(Unicidad::normalizar($valor), $categoria?->id)) {
                        $falla("Ya existe la categoría «{$otra->nombre}».");
                    }
                },
            ],
            'descripcion' => 'nullable|string|max:255',
            'imagen' => 'nullable|image|max:2048',
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->reglas());

        $datos = $request->only(['nombre', 'descripcion']);
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
        $request->validate($this->reglas($categoria));

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
