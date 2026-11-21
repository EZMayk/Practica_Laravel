<?php

namespace Database\Seeders;

use App\Models\DireccionEntrega;
use App\Models\SolicitudResumenEntrega;
use App\Models\User;
use Illuminate\Database\Seeder;

class SolicitudResumenEntregaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $cliente->id,
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);

        $solicitud = SolicitudResumenEntrega::factory()->create([
            'user_id' => $cliente->id,
            'direccion_entrega_id' => $direccion->id,
        ]);

        $this->command?->info("Solicitud ficticia {$solicitud->id} creada para verificar el estado pendiente.");
    }
}
