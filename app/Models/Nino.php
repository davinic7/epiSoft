<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Carbon\CarbonInterface;
use Database\Factories\NinoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Legajo de un niño o niña. Contiene datos personales sensibles (Ley
 * 25.326): toda consulta de un legajo debe dejar constancia con
 * registrarAcceso() (ver AuditaCambios).
 *
 * @property int $id
 * @property int $institucion_id
 * @property int|null $sala_id
 * @property string $apellido
 * @property string $nombre
 * @property string $dni
 * @property CarbonInterface $fecha_nacimiento
 * @property string|null $domicilio
 */
class Nino extends Model implements Auditable
{
    /** @use HasFactory<NinoFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion, SoftDeletes;

    protected $table = 'ninos';

    protected $fillable = ['apellido', 'nombre', 'dni', 'fecha_nacimiento', 'domicilio'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    /**
     * Sala a la que está asignado. La asignación es libre: cada EPI decide
     * qué niños van a cada sala, sin restricción de edad.
     *
     * @return BelongsTo<Sala, $this>
     */
    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class);
    }

    /**
     * Apellido y nombre, para mostrar en pantalla.
     */
    public function nombreCompleto(): string
    {
        return "{$this->apellido}, {$this->nombre}";
    }

    /**
     * Edad para mostrar en pantalla: en meses hasta el año ("8 meses") y en
     * años y meses después ("1 año y 3 meses").
     */
    public function edadLegible(?CarbonInterface $alDia = null): string
    {
        $meses = (int) floor($this->fecha_nacimiento->diffInMonths($alDia ?? now()));
        $anios = intdiv($meses, 12);
        $resto = $meses % 12;

        if ($anios === 0) {
            return trans_choice(':count mes|:count meses', $meses);
        }

        $textoAnios = trans_choice(':count año|:count años', $anios);

        return $resto === 0
            ? $textoAnios
            : __(':anios y :meses', ['anios' => $textoAnios, 'meses' => trans_choice(':count mes|:count meses', $resto)]);
    }
}
