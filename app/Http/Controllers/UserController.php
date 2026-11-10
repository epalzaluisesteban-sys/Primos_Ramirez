<?php

namespace App\Http\Controllers;

use App\Support\Almacen;
use App\Models\Abono;
use App\Models\AjusteInventario;
use App\Models\CierreCaja;
use App\Models\Compra;
use App\Models\Merma;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::latest()->get();

        // IDs de quienes tienen operaciones registradas: a ellos no se les puede
        // borrar, solo desactivar, así que la tabla muestra el botón que sí sirve.
        $conMovimientos = collect(self::MODELOS_CON_RESPONSABLE)
            ->flatMap(fn ($modelo) => $modelo::query()->distinct()->pluck('user_id'))
            ->filter()
            ->unique()
            ->flip();

        return view('usuarios.index', compact('usuarios', 'conMovimientos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'rol' => 'required|in:Dueno,Encargado,Obrero',
            'foto' => 'nullable|image|max:2048',
        ], [
            'email.unique' => 'El correo electrónico ya se encuentra registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $foto = $request->hasFile('foto') ? Almacen::guardar($request->file('foto'), 'usuarios') : null;

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'foto' => $foto,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Nuevo usuario registrado con éxito.');
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        $esUnoMismo = $usuario->id === auth()->id();

        $request->validate([
            'name' => 'required|string|max:255',
            'rol' => 'required|in:Dueno,Encargado,Obrero',
            'foto' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:8',
        ], [
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        $datos = [
            'name' => $request->name,
            'rol' => $request->rol,
            // Un usuario no puede desactivarse ni cambiarse el rol a sí mismo desde aquí.
            'activo' => $esUnoMismo ? true : $request->boolean('activo'),
        ];

        if ($request->filled('password')) {
            $datos['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('foto')) {
            if ($usuario->foto) {
                Almacen::borrar($usuario->foto);
            }
            $datos['foto'] = Almacen::guardar($request->file('foto'), 'usuarios');
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        // Evitar que el dueño se elimine a sí mismo
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta activa.']);
        }

        // Sus operaciones forman parte del historial contable: borrarlo las perdería
        // (o dejaría ventas sin responsable). En ese caso solo se puede desactivar.
        if ($this->tieneMovimientos($usuario)) {
            return back()->withErrors(['error' => "No se puede eliminar a {$usuario->name}: tiene ventas, compras, abonos, pérdidas o cierres de caja registrados. Desactívalo en su lugar para quitarle el acceso."]);
        }

        if ($usuario->foto) {
            Almacen::borrar($usuario->foto);
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Activa o desactiva el acceso de un trabajador sin pasar por el formulario de edición.
     * Es la forma de "quitarle el acceso" a quien ya tiene movimientos (no se puede borrar).
     */
    public function alternarActivo(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes desactivar tu propia cuenta.']);
        }

        $usuario->update(['activo' => !$usuario->activo]);

        return back()->with('success', $usuario->activo
            ? "{$usuario->name} puede volver a entrar al sistema."
            : "{$usuario->name} ya no puede entrar al sistema. Su historial se conserva.");
    }

    // Modelos que guardan quién hizo la operación (user_id).
    private const MODELOS_CON_RESPONSABLE = [Venta::class, Compra::class, Abono::class, Merma::class, CierreCaja::class, AjusteInventario::class];

    private function tieneMovimientos(User $usuario): bool
    {
        foreach (self::MODELOS_CON_RESPONSABLE as $modelo) {
            if ($modelo::where('user_id', $usuario->id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
