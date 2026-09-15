<?php

namespace App\Enums;

/**
 * Equipo técnico itinerante de la Dirección Provincial de Primera Infancia
 * (guía, cap. 2 "Equipos técnicos itinerantes de la Dirección Provincial de
 * Primera Infancia", pág. 15-17) al que pertenece un usuario, si depende de
 * la Dirección Provincial y no de una EPI puntual. Ver ADR-003
 * (docs/adr/003-equipos-tecnicos-provinciales.md) para la justificación
 * completa del modelo.
 *
 * A diferencia de RolInstitucional, esto NO reemplaza el mecanismo de roles
 * por institución: un técnico recorre varias EPI y, en cada una, tiene
 * asignado (vía spatie teams) el rol de spatie con este mismo valor. Esta
 * columna en users es la identidad organizacional ("depende de la Dirección
 * Provincial, no de ninguna EPI en particular"), no la autorización en sí.
 */
enum EquipoTecnicoProvincial: string
{
    /** Guía, cap. 2 "Equipo técnico interdisciplinario de abordaje global" (pág. 16). */
    case AbordajeGlobal = 'equipo técnico de abordaje global';

    /** Guía, cap. 2 "Equipo técnico de nutrición" (pág. 16). */
    case Nutricion = 'equipo técnico de nutrición';

    /** Guía, cap. 2 "Equipo técnico de trabajo social" (pág. 17). */
    case TrabajoSocial = 'equipo técnico de trabajo social';
}
