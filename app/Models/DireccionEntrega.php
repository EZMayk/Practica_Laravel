<?php

namespace App\Models;

use Database\Factories\DireccionEntregaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DireccionEntrega extends Model
{
    /** @use HasFactory<DireccionEntregaFactory> */
    use HasFactory;

    protected $fillable = [
        'etiqueta', 'direccion', 'telefono', 'observaciones', 'referencia', 'lat', 'lng',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }
}
