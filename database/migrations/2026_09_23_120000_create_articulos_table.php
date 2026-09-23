<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('categoria');
            $table->string('unidad_medida');
            $table->decimal('stock_minimo', 10, 2);
            $table->timestamps();

            $table->unique(['institucion_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};
