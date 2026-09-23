<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            // Días de anticipación con que se avisa que un lote está por
            // vencer (historia "Alertas de vencimiento y stock mínimo").
            $table->unsignedSmallInteger('dias_aviso_vencimiento')->default(30)->after('capacidad');
        });
    }

    public function down(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->dropColumn('dias_aviso_vencimiento');
        });
    }
};
