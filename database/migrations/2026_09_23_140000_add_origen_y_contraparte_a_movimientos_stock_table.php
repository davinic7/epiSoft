<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            // "Origen" en una entrada (de dónde viene: Dir. Prov. de
            // Primera Infancia, donación) o "destino" en una salida (fuera
            // de la institución o para uso interno), según la guía (cap.
            // 3.4, "Libro de registro de economato", pág. 23).
            $table->string('origen')->nullable()->after('fecha');
            // Quién entrega (en una entrada) o quién recibe (en una
            // salida): la guía exige que el libro quede firmado por ambos,
            // y este es el que no es la propia institución.
            $table->string('contraparte')->nullable()->after('origen');
            // Si este movimiento es un contramovimiento, el movimiento
            // original que anula. Los movimientos no se editan ni se
            // borran (ver App\Models\MovimientoStock); anularlos es crear
            // este segundo registro de signo contrario.
            $table->foreignId('anula_a_id')->nullable()->after('contraparte')->constrained('movimientos_stock')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anula_a_id');
            $table->dropColumn(['origen', 'contraparte']);
        });
    }
};
