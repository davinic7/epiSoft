<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\AsistenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Registro de asistencia de un niño en un día puntual: si asistió, a qué
 * sala (la que tenía asignada ese día, no la actual), horario de ingreso y
 * egreso, y quién lo retiró. Un niño tiene a lo sumo un registro por día
 * (ver el índice único en la migración).
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $nino_id
 * @property int $sala_id
 * @property Carbon $fecha
 * @property bool $presente
 * @property string|null $hora_ingreso
 * @property string|null $hora_egreso
 * @property int|null $retirado_por_id
 * @property string|null $observaciones
 */
class Asistencia extends Model implements Auditable
{
    /** @use HasFactory<AsistenciaFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $fillable = [
        'nino_id',
        'sala_id',
        'fecha',
        'presente',
        'hora_ingreso',
        'hora_egreso',
        'retirado_por_id',
        'observaciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'presente' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Nino, $this>
     */
    public function nino(): BelongsTo
    {
        return $this->belongsTo(Nino::class);
    }

    /**
     * @return BelongsTo<Sala, $this>
     */
    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class);
    }

    /**
     * @return BelongsTo<Referente, $this>
     */
    public function retiradoPor(): BelongsTo
    {
        return $this->belongsTo(Referente::class, 'retirado_por_id');
    }
}
