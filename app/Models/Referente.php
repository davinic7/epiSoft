<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\ReferenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Referente afectivo de uno o más niños (madre, padre, abuela, tutor,
 * etc.). El vínculo con cada niño —parentesco y si está autorizado a
 * retirarlo— vive en la tabla intermedia (ver Nino::referentes()), porque
 * un mismo referente puede tener un parentesco distinto según el niño.
 *
 * @property int $id
 * @property int $institucion_id
 * @property string $nombres
 * @property string $apellidos
 * @property string $dni
 * @property string|null $telefono
 * @property string|null $domicilio
 */
class Referente extends Model implements Auditable
{
    /** @use HasFactory<ReferenteFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $fillable = ['nombres', 'apellidos', 'dni', 'telefono', 'domicilio'];

    /**
     * @return BelongsToMany<Nino, $this, NinoReferente, 'pivot'>
     */
    public function ninos(): BelongsToMany
    {
        return $this->belongsToMany(Nino::class)
            ->using(NinoReferente::class)
            ->withPivot(['parentesco', 'autorizado_a_retirar'])
            ->withTimestamps();
    }
}
