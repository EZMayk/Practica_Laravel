<?php

namespace App\Services;

class NormalizadorDireccionEntrega
{
    /**
     * @param  array<string, mixed>  $entrada
     * @return array<string, mixed>
     */
    public function desdeDirecciones(array $entrada): array
    {
        return array_intersect_key($entrada, array_flip([
            'etiqueta', 'direccion', 'telefono', 'observaciones', 'referencia', 'lat', 'lng',
        ]));
    }

    /**
     * @param  array{direccion: array{etiqueta: string, direccion: string, telefono?: ?string, referencia?: ?string}, ubicacion: array{latitude: int|float, longitude: int|float}}  $entrada
     * @return array<string, mixed>
     */
    public function desdeCheckout(array $entrada): array
    {
        $direccion = $entrada['direccion'];
        $ubicacion = $entrada['ubicacion'];

        return [
            'etiqueta' => $direccion['etiqueta'],
            'direccion' => $direccion['direccion'],
            'telefono' => $direccion['telefono'] ?? null,
            'referencia' => $direccion['referencia'] ?? null,
            'lat' => $ubicacion['latitude'],
            'lng' => $ubicacion['longitude'],
        ];
    }
}
