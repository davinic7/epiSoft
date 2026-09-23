<?php

namespace App\Models;

use App\Enums\TipoMovimientoStock;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\MovimientoStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Un movimiento de entrada o salida de stock sobre un lote. Es la fuente
 * de verdad del stock (ver Lote::stockActual()). No se edita ni se borra:
 * para corregirlo se crea un contramovimiento de signo contrario que
 * apunta a este a través de anulaA() (ver
 * App\Livewire\Economato\Movimientos::anular()).
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $lote_id
 * @property TipoMovimientoStock $tipo
 * @property string $cantidad
 * @property Carbon $fecha
 * @property ?string $origen
 * @property ?string $contraparte
 * @property ?int $anula_a_id
 */
class MovimientoStock extends Model implements Auditable
{
    /** @use HasFactory<MovimientoStockFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'movimientos_stock';

    protected $fillable = ['lote_id', 'tipo', 'cantidad', 'fecha', 'origen', 'contraparte', 'anula_a_id'];

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

    /**
     * El movimiento original que este anula, si este es un contramovimiento.
     *
     * @return BelongsTo<MovimientoStock, $this>
     */
    public function anulaA(): BelongsTo
    {
        return $this->belongsTo(self::class, 'anula_a_id');
    }

    /**
     * El contramovimiento que anuló a este, si ya fue anulado.
     *
     * @return HasOne<MovimientoStock, $this>
     */
    public function contramovimiento(): HasOne
    {
        return $this->hasOne(self::class, 'anula_a_id');
    }
}
