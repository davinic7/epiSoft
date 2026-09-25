<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Educadoras asignadas a cada sala. No lleva institucion_id propio: la
 * sala ya pertenece a una institución y toda consulta pasa por ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sala_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sala_id')->constrained('salas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sala_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sala_user');
    }
};
