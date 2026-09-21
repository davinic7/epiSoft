<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\NinoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $institucion_id
 * @property string $nombres
 * @property string $apellidos
 * @property string|null $alias
 * @property string $dni
 * @property Carbon $fecha_nacimiento
 * @property string|null $lugar_nacimiento
 * @property string $domicilio
 * @property int|null $sala_id
 * @property Carbon $fecha_ingreso
 * @property string|null $observaciones
 */
class Nino extends Model implements Auditable
{
    /** @use HasFactory<NinoFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion, SoftDeletes;

    protected $fillable = [
        'nombres',
        'apellidos',
        'alias',
        'dni',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'domicilio',
        'sala_id',
        'fecha_ingreso',
        'observaciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_ingreso' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Sala, $this>
     */
    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class);
    }

    /**
     * @return BelongsToMany<Referente, $this, NinoReferente, 'pivot'>
     */
    public function referentes(): BelongsToMany
    {
        return $this->belongsToMany(Referente::class)
            ->using(NinoReferente::class)
            ->withPivot(['parentesco', 'autorizado_a_retirar'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<VacunaAplicada, $this>
     */
    public function vacunasAplicadas(): HasMany
    {
        return $this->hasMany(VacunaAplicada::class);
    }

    /**
     * @return HasMany<Alergia, $this>
     */
    public function alergias(): HasMany
    {
        return $this->hasMany(Alergia::class);
    }
}
