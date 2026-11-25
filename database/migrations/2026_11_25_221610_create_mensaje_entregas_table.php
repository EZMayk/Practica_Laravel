<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mensaje_entregas', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id')->unique();
            $table->string('correlation_id', 64)->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('direccion_entrega_id')->constrained('direccion_entregas')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('prioridad', 10);
            $table->string('estado_solicitud', 20);
            $table->string('destino', 40)->nullable();
            $table->string('estado_procesamiento', 20);
            $table->json('mensaje');
            $table->json('resultado')->nullable();
            $table->timestamp('procesado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensaje_entregas');
    }
};
