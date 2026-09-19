<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Carbon\CarbonInterface;
use Database\Factories\NinoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Legajo de un niño o niña. Contiene datos personales sensibles (Ley
 * 25.326): toda consulta de un legajo debe dejar constancia con
 * registrarAcceso() (ver AuditaCambios).
 *
 * @property int $id
 * @property int $institucion_id
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
     * Apellido y nombre, para mostrar en pantalla.
     */
    public function nombreCompleto(): string
    {
        return "{$this->apellido}, {$this->nombre}";
    }
}
