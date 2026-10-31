<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasaCambio extends Model
{
    protected $table = 'tasas_cambio';

    protected $fillable = ['fecha', 'tasa', 'fuente', 'user_id'];

    protected $casts = [
        'fecha' => 'date',
        'tasa' => 'float',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function esAutomatica(): bool
    {
        return $this->fuente === 'BCV';
    }
}
