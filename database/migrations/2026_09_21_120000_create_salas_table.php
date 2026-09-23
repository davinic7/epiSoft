<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('turno');
            $table->unsignedInteger('capacidad');
            $table->timestamps();

            $table->unique(['institucion_id', 'nombre', 'turno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salas');
    }
};
