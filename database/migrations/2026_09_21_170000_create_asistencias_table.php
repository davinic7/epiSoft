<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->foreignId('nino_id')->constrained('ninos')->cascadeOnDelete();
            // Sala a la que estaba asignado el niño ese día: la asistencia es
            // un hecho histórico y no debe moverse si más adelante cambia de
            // sala (ver Nino::sala_id).
            $table->foreignId('sala_id')->constrained('salas')->cascadeOnDelete();
            $table->date('fecha');
            $table->boolean('presente');
            $table->string('hora_ingreso')->nullable();
            $table->string('hora_egreso')->nullable();
            $table->foreignId('retirado_por_id')->nullable()->constrained('referentes')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['nino_id', 'fecha']);
            $table->index(['sala_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
