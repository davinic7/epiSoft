<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->foreignId('lote_id')->constrained('lotes')->cascadeOnDelete();
            $table->string('tipo');
            $table->decimal('cantidad', 10, 2);
            $table->date('fecha');
            $table->timestamps();

            $table->index(['lote_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
