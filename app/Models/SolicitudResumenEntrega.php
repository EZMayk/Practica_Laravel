<?php

namespace App\Models;

use Database\Factories\SolicitudResumenEntregaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudResumenEntrega extends Model
{
    /** @use HasFactory<SolicitudResumenEntregaFactory> */
    use HasFactory;

    protected $fillable = [
        'message_id', 'correlation_id', 'user_id', 'direccion_entrega_id', 'estado', 'mensaje', 'resultado', 'procesado_en',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<DireccionEntrega, $this> */
    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(DireccionEntrega::class);
    }

    protected function casts(): array
    {
        return [
            'mensaje' => 'array',
            'resultado' => 'array',
            'procesado_en' => 'datetime',
        ];
    }
}
