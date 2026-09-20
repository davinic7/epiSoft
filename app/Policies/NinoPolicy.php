<?php

namespace App\Policies;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Models\User;

/**
 * Se autoriza con los permisos ninos.* de la institución activa.
 */
class NinoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Ver));
    }

    public function view(User $user, Nino $nino): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Ver));
    }

    public function create(User $user): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Crear));
    }

    public function update(User $user, Nino $nino): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Editar));
    }

    public function delete(User $user, Nino $nino): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Eliminar));
    }
}
