<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Los correos se guardan en minúsculas: "Ana@Correo.com" entra igual que "ana@correo.com"
        $request->merge(['email' => User::normalizarCorreo($request->input('email'))]);
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            if (!Auth::user()->activo) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            Auth::user()->update(['ultimo_acceso' => now()]);

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}