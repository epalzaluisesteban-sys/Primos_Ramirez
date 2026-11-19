<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Almacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => auth()->user()
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->merge(['email' => User::normalizarCorreo($request->input('email'))]);
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'foto' => ['nullable', 'image', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', 'min:8', 'max:100'],
        ], [
            'email.unique' => 'Ese correo ya lo usa otra persona del sistema.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('foto')) {
            if ($user->foto) {
                Almacen::borrar($user->foto);
            }
            $user->foto = Almacen::guardar($request->file('foto'), 'usuarios');
        }

        $user->save();

        return back()->with('success', 'Perfil y credenciales actualizados correctamente.');
    }
}