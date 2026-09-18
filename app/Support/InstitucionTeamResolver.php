<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Conecta spatie/laravel-permission (teams) con la misma institución activa
 * que ya usa InstitucionScope para filtrar datos: un usuario tiene un rol
 * distinto según la institución en la que está trabajando, y es la misma
 * noción de "institución activa" en ambos lugares, no dos independientes.
 */
class InstitucionTeamResolver implements PermissionsTeamResolver
{
    public function getPermissionsTeamId(): int|string|null
    {
        return app(InstitucionContext::class)->id();
    }

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        app(InstitucionContext::class)->set($id);
    }
}
