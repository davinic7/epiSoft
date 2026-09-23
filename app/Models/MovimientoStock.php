<?php

namespace App\Models;

use App\Enums\TipoMovimientoStock;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\MovimientoStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Un movimiento de entrada o salida de stock sobre un lote. Es la fuente
 * de verdad del stock (ver Lote::stockActual()); el libro de movimientos
 * completo (con origen, quién recibe/entrega, consumo por vencimiento y
 * anulación por contramovimiento) es la historia "Libro de movimientos de
 * entrada y salida" del backlog.
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $lote_id
 * @property TipoMovimientoStock $tipo
 * @property string $cantidad
 * @property Carbon $fecha
 */
class MovimientoStock extends Model implements Auditable
{
    /** @use HasFactory<MovimientoStockFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'movimientos_stock';

    protected $fillable = ['lote_id', 'tipo', 'cantidad', 'fecha'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimientoStock::class,
            'cantidad' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Lote, $this>
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
