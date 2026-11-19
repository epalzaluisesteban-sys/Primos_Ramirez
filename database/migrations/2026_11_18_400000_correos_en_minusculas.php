<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los correos de los usuarios pasan a minúsculas (el inicio de sesión ya los compara así). Si dos cuentas
 * quedaran con el mismo correo, la que llegó después se deja como está para no chocar con el índice único:
 * el Dueño puede corregirla desde Personal.
 */
return new class extends Migration
{
    public function up(): void
    {
        $usuarios = DB::table('users')->orderBy('id')->get(['id', 'email']);
        // Correos tal como están: si la versión en minúsculas ya es de otra cuenta, esta no se toca
        $usados = $usuarios->pluck('email')->flip()->all();

        foreach ($usuarios as $usuario) {
            $correo = mb_strtolower(trim($usuario->email));
            if ($correo === $usuario->email || isset($usados[$correo])) {
                continue;
            }
            DB::table('users')->where('id', $usuario->id)->update(['email' => $correo]);
            unset($usados[$usuario->email]);
            $usados[$correo] = true;
        }
    }

    public function down(): void
    {
        // Los correos en minúsculas siguen siendo válidos: no hay nada que deshacer
    }
};
