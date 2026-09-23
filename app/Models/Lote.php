<?php

namespace App\Models;

use App\Enums\TipoMovimientoStock;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\LoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Un lote es la unidad de ingreso de stock de un artículo: cada ingreso
 * genera uno nuevo con su propia fecha de vencimiento, aunque sea del
 * mismo artículo que un lote ya existente. No tiene un campo de cantidad
 * editable: el stock se calcula sumando sus movimientos (ver
 * stockActual()), así nunca puede desincronizarse del libro de
 * movimientos.
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $articulo_id
 * @property Carbon $fecha_vencimiento
 */
class Lote extends Model implements Auditable
{
    /** @use HasFactory<LoteFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $fillable = ['articulo_id', 'fecha_vencimiento'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Articulo, $this>
     */
    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    /**
     * @return HasMany<MovimientoStock, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    /**
     * Stock actual del lote: suma de entradas menos suma de salidas. Se
     * calcula desde los movimientos cargados en $this->movimientos (usar
     * with('movimientos') para evitar N+1 al listar varios lotes).
     */
    public function stockActual(): float
    {
        return $this->movimientos->sum(
            fn (MovimientoStock $movimiento): float => $movimiento->tipo === TipoMovimientoStock::Entrada
                ? (float) $movimiento->cantidad
                : -(float) $movimiento->cantidad
        );
    }
}
