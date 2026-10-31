<?php

namespace App\Http\Controllers;

use App\Support\Almacen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::latest()->get();
        return view('usuarios.index', compact('usuarios'));
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

        if ($usuario->foto) {
            Almacen::borrar($usuario->foto);
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
