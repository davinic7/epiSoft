<?php

namespace App\Models;

use App\Enums\SeveridadAlergia;
use App\Enums\TipoAlergia;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\AlergiaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Alergia o restricción alimentaria de un niño. Se muestra destacada en su
 * legajo y en la vista de la sala a la que asiste (ver
 * App\Livewire\Salas\Ver) para que educadoras y personal de cocina la
 * tengan siempre a la vista.
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $nino_id
 * @property TipoAlergia $tipo
 * @property SeveridadAlergia $severidad
 * @property string $descripcion
 * @property string|null $observaciones
 */
class Alergia extends Model implements Auditable
{
    /** @use HasFactory<AlergiaFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $fillable = ['nino_id', 'tipo', 'severidad', 'descripcion', 'observaciones'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoAlergia::class,
            'severidad' => SeveridadAlergia::class,
        ];
    }

    /**
     * @return BelongsTo<Nino, $this>
     */
    public function nino(): BelongsTo
    {
        return $this->belongsTo(Nino::class);
    }
}
