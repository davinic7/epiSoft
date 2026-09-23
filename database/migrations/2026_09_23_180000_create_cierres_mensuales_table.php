<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->date('periodo');
            $table->foreignId('cerrado_por_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('cerrado_en');
            $table->foreignId('reabierto_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reabierto_en')->nullable();
            $table->timestamps();

            // No es unique: un período reabierto se puede volver a cerrar,
            // y eso agrega una fila nueva en vez de reescribir la anterior
            // (ver App\Models\CierreMensual::estaCerradoParaFecha()).
            $table->index(['institucion_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_mensuales');
    }
};
