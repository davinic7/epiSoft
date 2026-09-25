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
        Schema::table('ninos', function (Blueprint $table) {
            $table->foreignId('sala_id')->nullable()->after('institucion_id')->constrained('salas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ninos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sala_id');
        });
    }
};
