<?php

namespace App\Enums;

/**
 * Acción sobre la que se autoriza dentro de un módulo. Todo permiso del
 * sistema es la combinación de un Modulo y una AccionPermiso (ver
 * Modulo::permiso()).
 */
enum AccionPermiso: string
{
    case Ver = 'ver';
    case Crear = 'crear';
    case Editar = 'editar';
    case Eliminar = 'eliminar';
}
