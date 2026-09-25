<?php

namespace App\Models;

use App\Enums\Turno;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\SalaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $institucion_id
 * @property string $nombre
 * @property Turno $turno
 * @property int $capacidad
 */
class Sala extends Model implements Auditable
{
    /** @use HasFactory<SalaFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion, SoftDeletes;

    protected $table = 'salas';

    protected $fillable = ['nombre', 'turno', 'capacidad'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'turno' => Turno::class,
            'capacidad' => 'integer',
        ];
    }
}
