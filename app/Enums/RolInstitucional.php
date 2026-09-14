<?php

namespace App\Enums;

/**
 * Roles que spatie/laravel-permission escopa por institución (teams, ver
 * config/permission.php). Un mismo usuario puede tener un rol distinto en
 * cada institución a la que pertenece.
 *
 * El superadmin NO está acá: es transversal a todas las instituciones y
 * spatie/laravel-permission no tiene forma nativa de que una asignación
 * aplique a todas las instituciones a la vez. Se modela aparte, como
 * columna is_superadmin en users y bypass de Gate (ver
 * AppServiceProvider::boot() y App\Models\Scopes\InstitucionScope).
 */
enum RolInstitucional: string
{
    case Direccion = 'dirección';
    case Docente = 'docente';
    case Administrativo = 'administrativo';
    case Nutricion = 'nutrición';
}
