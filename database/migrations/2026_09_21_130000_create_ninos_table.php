<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ninos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('alias')->nullable();
            $table->string('dni');
            $table->date('fecha_nacimiento');
            $table->string('lugar_nacimiento')->nullable();
            $table->string('domicilio');
            $table->foreignId('sala_id')->nullable()->constrained('salas')->nullOnDelete();
            $table->date('fecha_ingreso');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institucion_id', 'dni']);
            $table->index(['institucion_id', 'apellidos', 'nombres']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ninos');
    }
};
