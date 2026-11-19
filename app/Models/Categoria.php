<?php

namespace App\Models;

use App\Support\Almacen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use Concerns\TieneNombreUnico;

    protected $fillable = ['nombre', 'descripcion', 'imagen'];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    // Proveedores que marcaron esta categoría como algo que distribuyen
    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'categoria_proveedor')->orderBy('nombre');
    }

    public function imagenUrl(): ?string
    {
        return Almacen::url($this->imagen);
    }
}