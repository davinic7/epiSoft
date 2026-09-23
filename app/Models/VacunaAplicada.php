<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\VacunaAplicadaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Registro de que a un niño se le aplicó una dosis del calendario de
 * vacunación (ver config/calendario_vacunacion.php, referenciada por
 * "vacuna_clave"). El cálculo de qué dosis están atrasadas vive en
 * App\Services\CalendarioDeVacunacion, no acá.
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $nino_id
 * @property string $vacuna_clave
 * @property Carbon $fecha_aplicacion
 * @property string|null $observaciones
 */
class VacunaAplicada extends Model implements Auditable
{
    /** @use HasFactory<VacunaAplicadaFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'vacunas_aplicadas';

    protected $fillable = ['nino_id', 'vacuna_clave', 'fecha_aplicacion', 'observaciones'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date',
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
