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
        Schema::table('instituciones', function (Blueprint $table) {
            $table->string('direccion')->nullable()->after('nombre');
            $table->string('cuit')->nullable()->unique()->after('direccion');
            $table->string('referente')->nullable()->after('cuit');
            $table->unsignedInteger('capacidad')->nullable()->after('referente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'cuit', 'referente', 'capacidad']);
        });
    }
};
