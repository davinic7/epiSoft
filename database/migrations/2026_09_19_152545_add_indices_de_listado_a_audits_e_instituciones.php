<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El listado de auditoría filtra por institución y ordena por fecha.
        Schema::table('audits', function (Blueprint $table) {
            $table->index(['institucion_id', 'created_at'], 'audits_institucion_id_created_at_index');
        });

        // El listado y el selector de instituciones ordenan por nombre.
        Schema::table('instituciones', function (Blueprint $table) {
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('audits_institucion_id_created_at_index');
        });

        Schema::table('instituciones', function (Blueprint $table) {
            $table->dropIndex(['nombre']);
        });
    }
};
