<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('dni');
            $table->string('telefono')->nullable();
            $table->string('domicilio')->nullable();
            $table->timestamps();

            $table->unique(['institucion_id', 'dni']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referentes');
    }
};
