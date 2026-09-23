<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bienes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('codigo');
            $table->string('ubicacion');
            $table->string('estado_conservacion');
            $table->timestamps();

            $table->unique(['institucion_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bienes');
    }
};
