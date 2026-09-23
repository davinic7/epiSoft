<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nino_referente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nino_id')->constrained('ninos')->cascadeOnDelete();
            $table->foreignId('referente_id')->constrained('referentes')->cascadeOnDelete();
            $table->string('parentesco');
            $table->boolean('autorizado_a_retirar')->default(false);
            $table->timestamps();

            $table->unique(['nino_id', 'referente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nino_referente');
    }
};
