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
        Schema::create('direccion_entregas', function (Blueprint $table) {
            $table->id();
            $table->string('etiqueta', 40);
            $table->string('direccion', 200);
            $table->string('telefono', 20)->nullable();
            $table->string('observaciones', 200)->nullable();
            $table->string('referencia', 200)->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direccion_entregas');
    }
};
