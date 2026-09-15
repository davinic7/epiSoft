<?php

namespace App\Enums;

/**
 * Roles que spatie/laravel-permission escopa por institución (teams, ver
 * config/permission.php). Un mismo usuario puede tener un rol distinto en
 * cada institución a la que pertenece.
 *
 * Corresponde uno a uno con los actores institucionales del capítulo 2 de
 * docs/guia-buenas-practicas-epi.md ("Actores institucionales"), es decir,
 * el personal que trabaja dentro de un EPI. NO incluye a los equipos
 * técnicos itinerantes de la Dirección Provincial de Primera Infancia
 * (abordaje global, nutrición, trabajo social): esos equipos recorren
 * varias instituciones a la vez, así que se modelan aparte, en
 * EquipoTecnicoProvincial (ver ADR-003,
 * docs/adr/003-equipos-tecnicos-provinciales.md).
 *
 * El superadmin NO está acá: es transversal a todas las instituciones y
 * spatie/laravel-permission no tiene forma nativa de que una asignación
 * aplique a todas las instituciones a la vez. Se modela aparte, como
 * columna is_superadmin en users y bypass de Gate (ver
 * AppServiceProvider::boot() y App\Models\Scopes\InstitucionScope).
 */
enum RolInstitucional: string
{
    /** Guía, cap. 2 "Equipo de coordinación" (pág. 10-12). */
    case EquipoCoordinacion = 'equipo de coordinación';

    /** Guía, cap. 2.2 "Encargada/o de recepción" y Anexo 1, Ficha 1 (pág. 13, 58). */
    case EncargadoRecepcion = 'encargado de recepción';

    /** Guía, cap. 2.2 "Coordinador/a pedagógico/a" y Anexo 1, Ficha 2 (pág. 13, 59). */
    case CoordinadorPedagogico = 'coordinador pedagógico';

    /** Guía, cap. 2.2 "Educadoras/es" y Anexo 1, Ficha 3 (pág. 14, 60). */
    case Educador = 'educador';

    /** Guía, cap. 2.2 "Encargado/a de economato" y Anexo 1, Ficha 4 (pág. 15, 60). */
    case EncargadoEconomato = 'encargado de economato';

    /** Guía, cap. 2.2 "Personal de cocina" y Anexo 1, Ficha 5 (pág. 15, 61). */
    case PersonalCocina = 'personal de cocina';

    /** Guía, cap. 2.2 "Personal de mantenimiento y limpieza" y Anexo 1, Ficha 6 (pág. 15, 62). */
    case PersonalMantenimiento = 'personal de mantenimiento y limpieza';
}
