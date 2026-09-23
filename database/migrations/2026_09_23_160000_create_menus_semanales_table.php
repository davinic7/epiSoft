<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus_semanales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->date('semana_inicio');
            $table->string('estado')->default('borrador');
            $table->foreignId('aprobado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamps();

            $table->unique(['institucion_id', 'semana_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus_semanales');
    }
};
