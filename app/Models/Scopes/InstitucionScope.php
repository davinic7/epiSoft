<?php

namespace App\Models\Scopes;

use App\Support\InstitucionContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtra toda consulta por la institución activa de la sesión. Si no hay
 * ninguna institución activa, no debe verse ningún registro: fail-closed,
 * nunca fail-open. Un id inexistente (-1) logra ese cierre sin depender de
 * columnas nulas ni de condicionales que alguien pueda borrar por error.
 *
 * El bypass de Gate del superadmin (ver AppServiceProvider::boot()) es
 * intencionalmente ajeno a este scope: is_superadmin salta las
 * autorizaciones (Gate/Policy), pero no el filtrado de datos. Un superadmin
 * sin institución activa sigue viendo cero filas acá, igual que cualquier
 * usuario — para operar sobre los datos de una institución puntual tiene
 * que seleccionarla como cualquiera (InstitucionContext::set()), no hay un
 * segundo camino de lectura "sin institución" que mantener sincronizado.
 *
 * @implements Scope<Model>
 */
class InstitucionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $institucionId = app(InstitucionContext::class)->id();

        $builder->where($model->qualifyColumn('institucion_id'), $institucionId ?? -1);
    }
}
