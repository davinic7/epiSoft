<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\MovimientoDeUbicacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Historial de cambios de ubicación de un bien del inventario
 * patrimonial. Se crea desde Bien::moverA(), nunca a mano.
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $bien_id
 * @property ?string $ubicacion_anterior
 * @property string $ubicacion_nueva
 * @property Carbon $fecha
 */
class MovimientoDeUbicacion extends Model implements Auditable
{
    /** @use HasFactory<MovimientoDeUbicacionFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'movimientos_de_ubicacion';

    protected $fillable = ['bien_id', 'ubicacion_anterior', 'ubicacion_nueva', 'fecha'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Bien, $this>
     */
    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }
}
