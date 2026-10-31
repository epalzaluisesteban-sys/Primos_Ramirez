<?php

namespace App\Models\Concerns;

use App\Support\Almacen;
trait TieneFoto
{
    // URL pública de la imagen guardada en storage/app/public (null si no hay).
    public function fotoUrl(): ?string
    {
        return Almacen::url($this->foto);
    }
}
