<?php

namespace Database\Seeders;

use App\Models\MensajeEntrega;
use Illuminate\Database\Seeder;

class MensajeEntregaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MensajeEntrega::factory()->create();
    }
}
