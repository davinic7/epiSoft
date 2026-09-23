<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_de_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->foreignId('menu_semanal_id')->constrained('menus_semanales')->cascadeOnDelete();
            $table->string('dia');
            $table->string('comida');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['menu_semanal_id', 'dia', 'comida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_de_menu');
    }
};
