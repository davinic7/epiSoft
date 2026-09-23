<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacunas_aplicadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->foreignId('nino_id')->constrained('ninos')->cascadeOnDelete();
            $table->string('vacuna_clave');
            $table->date('fecha_aplicacion');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['nino_id', 'vacuna_clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacunas_aplicadas');
    }
};
