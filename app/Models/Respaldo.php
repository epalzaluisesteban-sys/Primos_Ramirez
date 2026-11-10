<?php

namespace App\Models;

use App\Services\RespaldoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Respaldo extends Model
{
    protected $fillable = ['archivo', 'tipo', 'autor', 'nota', 'tamano', 'registros', 'fotos'];

    public function ruta(): string
    {
        return RespaldoService::CARPETA . '/' . $this->archivo;
    }

    // En Render el disco del servidor es temporal: el archivo puede ya no estar aunque quede el registro.
    public function existeArchivo(): bool
    {
        return Storage::disk(RespaldoService::DISCO)->exists($this->ruta());
    }

    public function tamanoLegible(): string
    {
        $kb = $this->tamano / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1) . ' MB' : number_format(max($kb, 1), 0) . ' KB';
    }
}
