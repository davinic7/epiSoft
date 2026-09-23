<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_de_ubicacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->foreignId('bien_id')->constrained('bienes')->cascadeOnDelete();
            $table->string('ubicacion_anterior')->nullable();
            $table->string('ubicacion_nueva');
            $table->date('fecha');
            $table->timestamps();

            $table->index(['bien_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_de_ubicacion');
    }
};
