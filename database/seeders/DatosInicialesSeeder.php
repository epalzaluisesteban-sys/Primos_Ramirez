<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario Admin/Dueño de prueba
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@primosramirez.com',
            'password' => Hash::make('12345678'),
            'rol' => 'Dueno',
        ]);

        // Categorías iniciales
        $frutas = Categoria::create(['nombre' => 'Frutas', 'descripcion' => 'Frutas frescas del día']);
        $verduras = Categoria::create(['nombre' => 'Verduras', 'descripcion' => 'Hortalizas y verduras varias']);

        // Productos de ejemplo
        Producto::create(['categoria_id' => $frutas->id, 'nombre' => 'Cambur (Kg)', 'precio' => 1.50, 'stock' => 120]);
        Producto::create(['categoria_id' => $frutas->id, 'nombre' => 'Manzana (Kg)', 'precio' => 3.00, 'stock' => 80]);
        Producto::create(['categoria_id' => $verduras->id, 'nombre' => 'Papa (Kg)', 'precio' => 1.20, 'stock' => 200]);
        Producto::create(['categoria_id' => $verduras->id, 'nombre' => 'Tomate (Kg)', 'precio' => 2.00, 'stock' => 150]);
    }
}