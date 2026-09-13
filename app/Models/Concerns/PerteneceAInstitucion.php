<?php

namespace App\Models\Concerns;

use App\Models\Institucion;
use App\Models\Scopes\InstitucionScope;
use App\Support\InstitucionContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cualquier modelo de negocio que guarde datos propios de una institución
 * debe usar este trait: aplica el filtro por institución activa a toda
 * consulta (ver InstitucionScope) y completa institucion_id al crear, sin
 * que cada desarrollador tenga que repetirlo a mano en cada modelo o query.
 */
trait PerteneceAInstitucion
{
    public static function bootPerteneceAInstitucion(): void
    {
        static::addGlobalScope(new InstitucionScope);

        static::creating(function ($model) {
            if (is_null($model->institucion_id)) {
                $model->institucion_id = app(InstitucionContext::class)->id();
            }
        });
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class);
    }
}
