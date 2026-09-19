<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\InstitucionContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja la institución activa de la sesión (ver InstitucionContext) siempre
 * dentro de las que el usuario puede usar: conserva la elegida mientras
 * siga siendo válida y, si no hay ninguna, fija la única disponible. Con
 * varias disponibles queda sin institución (fail-closed, ver
 * InstitucionScope) hasta que el usuario elija una en el selector.
 *
 * Revalida en cada request, así que una institución dada de baja o un
 * cambio de membresía se reflejan de inmediato y un valor manipulado en
 * sesión nunca da acceso a datos de otra institución.
 */
class EstablecerInstitucionActiva
{
    public function __construct(private readonly InstitucionContext $contexto) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario instanceof User) {
            $this->resolver($usuario);
        }

        return $next($request);
    }

    private function resolver(User $usuario): void
    {
        $disponibles = $usuario->institucionesAccesibles();
        $activa = $this->contexto->id();

        if ($activa !== null && (clone $disponibles)->whereKey($activa)->exists()) {
            return;
        }

        $ids = $disponibles->limit(2)->pluck('instituciones.id');

        $this->contexto->set($ids->count() === 1 ? $ids->first() : null);
    }
}
