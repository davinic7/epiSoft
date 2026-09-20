<?php

namespace App\Policies;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Sala;
use App\Models\User;

/**
 * Las salas pertenecen al módulo de niños: se autorizan con los permisos
 * ninos.* de la institución activa.
 */
class SalaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Ver));
    }

    public function view(User $user, Sala $sala): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Ver));
    }

    public function create(User $user): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Crear));
    }

    public function update(User $user, Sala $sala): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Editar));
    }

    public function delete(User $user, Sala $sala): bool
    {
        return $user->can(Modulo::Ninos->permiso(AccionPermiso::Eliminar));
    }
}
