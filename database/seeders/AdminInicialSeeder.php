<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea el primer usuario Dueño en un sistema nuevo (producción), con los datos de las
 * variables de entorno ADMIN_NAME, ADMIN_EMAIL y ADMIN_PASSWORD. No hace nada si ya existe un Dueño
 * o si falta la contraseña, así que es seguro ejecutarlo en cada arranque.
 */
class AdminInicialSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('app.admin_inicial.password');

        if (!$password || User::where('rol', 'Dueno')->exists()) {
            return;
        }

        User::create([
            'name' => config('app.admin_inicial.name'),
            'email' => config('app.admin_inicial.email'),
            'password' => Hash::make($password),
            'rol' => 'Dueno',
        ]);
    }
}
