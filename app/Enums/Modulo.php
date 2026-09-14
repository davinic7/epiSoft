<?php

namespace App\Enums;

/**
 * Módulo de negocio sobre el que se otorgan permisos. La lista sigue las
 * áreas de docs/BACKLOG.md; un módulo puede existir acá antes de tener
 * pantallas propias, porque el modelo de permisos es la base sobre la que
 * cada milestone construye su autorización.
 *
 * "instituciones" no es un módulo de esta lista: su CRUD es exclusivo del
 * superadmin (bypass de Gate, no permiso de spatie), no algo que un rol
 * institucional pueda tener.
 */
enum Modulo: string
{
    case Usuarios = 'usuarios';
    case Ninos = 'ninos';
    case Economato = 'economato';
    case Pedagogico = 'pedagogico';
    case Rrhh = 'rrhh';
    case Institucional = 'institucional';
    case Alertas = 'alertas';

    /**
     * Nombre del permiso de spatie para esta acción en este módulo, con la
     * convención "modulo.accion" (por ejemplo "ninos.editar").
     */
    public function permiso(AccionPermiso $accion): string
    {
        return "{$this->value}.{$accion->value}";
    }
}
