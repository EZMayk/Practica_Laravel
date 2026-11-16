<?php

namespace Database\Factories;

use App\Models\DireccionEntrega;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DireccionEntrega>
 */
class DireccionEntregaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
        ];
    }
}
