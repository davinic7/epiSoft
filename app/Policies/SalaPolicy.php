<?php

namespace App\Policies;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Sala;
use App\Models\User;

/**
 * Se autoriza con los permisos salas.* de la institución activa. Asignar o
 * mover niños entre salas es editar la sala (update).
 */
class SalaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Modulo::Salas->permiso(AccionPermiso::Ver));
    }

    public function view(User $user, Sala $sala): bool
    {
        return $user->can(Modulo::Salas->permiso(AccionPermiso::Ver));
    }

    public function create(User $user): bool
    {
        return $user->can(Modulo::Salas->permiso(AccionPermiso::Crear));
    }

    public function update(User $user, Sala $sala): bool
    {
        return $user->can(Modulo::Salas->permiso(AccionPermiso::Editar));
    }

    public function delete(User $user, Sala $sala): bool
    {
        return $user->can(Modulo::Salas->permiso(AccionPermiso::Eliminar));
    }
}
