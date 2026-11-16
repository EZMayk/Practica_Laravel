<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DireccionEntregaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etiqueta' => $this->etiqueta,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'observaciones' => $this->observaciones,
            'referencia' => $this->referencia,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'activa' => $this->activa,
            'creadoAt' => $this->created_at->toIso8601String(),
        ];
    }
}
