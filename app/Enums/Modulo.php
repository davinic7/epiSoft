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
 *
 * "Vulneraciones" está separado de "Institucional" (y no es un sub-caso
 * suyo) porque agrupan datos de sensibilidad muy distinta: mapa de riesgo,
 * articulaciones y recursero comunitario son de consulta amplia, mientras
 * que el registro de sospecha de vulneración de derechos (guía, cap. 6.1)
 * exige acceso restringido a los pocos actores que la guía nombra ahí
 * (equipo de coordinación y los equipos técnicos provinciales de abordaje
 * global y trabajo social) y auditoría reforzada (docs/backlog.txt, issue
 * "Módulo de vulneración de derechos").
 *
 * "Salas" está separado de "Ninos" porque quién arma las salas y asigna a
 * los niños (equipo de coordinación junto al coordinador pedagógico) no es
 * lo mismo que quién carga o edita un legajo (docs/backlog.txt, issue
 * "Salas con descripción y asignación de niños").
 */
enum Modulo: string
{
    case Usuarios = 'usuarios';
    case Ninos = 'ninos';
    case Salas = 'salas';
    case Economato = 'economato';
    case Pedagogico = 'pedagogico';
    case Rrhh = 'rrhh';
    case Institucional = 'institucional';
    case Vulneraciones = 'vulneraciones';
    case Alertas = 'alertas';
    case Auditoria = 'auditoria';

    /**
     * Nombre del permiso de spatie para esta acción en este módulo, con la
     * convención "modulo.accion" (por ejemplo "ninos.editar").
     */
    public function permiso(AccionPermiso $accion): string
    {
        return "{$this->value}.{$accion->value}";
    }
}
