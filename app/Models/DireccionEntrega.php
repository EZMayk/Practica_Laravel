<?php

namespace App\Models;

use Database\Factories\DireccionEntregaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DireccionEntrega extends Model
{
    /** @use HasFactory<DireccionEntregaFactory> */
    use HasFactory;

    protected $fillable = [
        'etiqueta', 'direccion', 'telefono', 'observaciones', 'referencia', 'lat', 'lng',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }
}
