<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Observers\InstitucionObserver;
use Database\Factories\InstitucionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(InstitucionObserver::class)]
class Institucion extends Model implements Auditable
{
    /** @use HasFactory<InstitucionFactory> */
    use AuditaCambios, HasFactory, SoftDeletes;

    protected $table = 'instituciones';

    protected $fillable = ['nombre', 'direccion', 'cuit', 'referente', 'capacidad', 'dias_aviso_vencimiento'];

    /**
     * Una institución se audita en sí misma, no en la institución activa.
     */
    protected function institucionParaAuditoria(): ?int
    {
        return $this->id;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'dias_aviso_vencimiento' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
