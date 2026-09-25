<?php

namespace App\Models;

use App\Enums\Turno;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\SalaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $institucion_id
 * @property string $nombre
 * @property string|null $descripcion
 * @property Turno $turno
 * @property int $capacidad
 * @property int|null $ninos_count
 */
class Sala extends Model implements Auditable
{
    /** @use HasFactory<SalaFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion, SoftDeletes;

    protected $table = 'salas';

    protected $fillable = ['nombre', 'descripcion', 'turno', 'capacidad'];

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

    /**
     * @return HasMany<Nino, $this>
     */
    public function ninos(): HasMany
    {
        return $this->hasMany(Nino::class);
    }

    /**
     * Educadoras asignadas a la sala. Una sala puede tener una o más y una
     * educadora puede estar en más de una sala.
     *
     * @return BelongsToMany<User, $this>
     */
    public function educadoras(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Si la sala tiene más niños asignados que su capacidad máxima. El
     * sistema lo avisa pero no lo impide: cada EPI decide. Usa ninos_count
     * si la consulta lo cargó con withCount().
     */
    public function superaCupo(): bool
    {
        return ($this->ninos_count ?? $this->ninos()->count()) > $this->capacidad;
    }
}
