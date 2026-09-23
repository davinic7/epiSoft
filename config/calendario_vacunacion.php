<?php

/**
 * Calendario Nacional de Vacunación (Argentina) usado para calcular si una
 * dosis está "atrasada" según la edad del niño.
 *
 * ADVERTENCIA — BORRADOR SIN VALIDAR: esta lista fue armada con
 * conocimiento general del esquema de vacunación argentino, no fue
 * contrastada dosis por dosis con la fuente oficial (Ministerio de Salud
 * de la Nación, https://www.argentina.gob.ar/salud/calendario-vacunacion)
 * ni revisada por personal de salud. Los nombres, edades y cantidad de
 * dosis pueden tener errores o estar desactualizados. Antes de usar este
 * calendario para decidir si una vacuna real está atrasada, hay que
 * revisarlo contra la fuente oficial vigente y corregir lo que haga
 * falta acá — es la única fuente que consulta el cálculo de estado (ver
 * App\Services\CalendarioDeVacunacion), así que corregirlo acá alcanza,
 * sin tocar código.
 *
 * Cada entrada es una dosis puntual, no una vacuna genérica (por eso
 * "pentavalente" aparece tres veces con distinta clave y edad). "clave"
 * es estable y no debe cambiar una vez que haya datos cargados
 * (Nino::vacunasAplicadas() la referencia por valor, no por posición).
 */
return [
    'vacunas' => [
        ['clave' => 'bcg', 'nombre' => 'BCG', 'dosis' => 'Única', 'edad_meses' => 0],
        ['clave' => 'hepatitis_b_rn', 'nombre' => 'Hepatitis B', 'dosis' => 'Recién nacido', 'edad_meses' => 0],
        ['clave' => 'pentavalente_1', 'nombre' => 'Pentavalente/Quíntuple', 'dosis' => '1ª dosis', 'edad_meses' => 2],
        ['clave' => 'ipv_1', 'nombre' => 'IPV (polio inactivada)', 'dosis' => '1ª dosis', 'edad_meses' => 2],
        ['clave' => 'neumococo_1', 'nombre' => 'Neumococo conjugada', 'dosis' => '1ª dosis', 'edad_meses' => 2],
        ['clave' => 'rotavirus_1', 'nombre' => 'Rotavirus', 'dosis' => '1ª dosis', 'edad_meses' => 2],
        ['clave' => 'pentavalente_2', 'nombre' => 'Pentavalente/Quíntuple', 'dosis' => '2ª dosis', 'edad_meses' => 4],
        ['clave' => 'ipv_2', 'nombre' => 'IPV (polio inactivada)', 'dosis' => '2ª dosis', 'edad_meses' => 4],
        ['clave' => 'neumococo_2', 'nombre' => 'Neumococo conjugada', 'dosis' => '2ª dosis', 'edad_meses' => 4],
        ['clave' => 'rotavirus_2', 'nombre' => 'Rotavirus', 'dosis' => '2ª dosis', 'edad_meses' => 4],
        ['clave' => 'pentavalente_3', 'nombre' => 'Pentavalente/Quíntuple', 'dosis' => '3ª dosis', 'edad_meses' => 6],
        ['clave' => 'opv_refuerzo_6m', 'nombre' => 'OPV (polio oral)', 'dosis' => 'Refuerzo', 'edad_meses' => 6],
        ['clave' => 'triple_viral', 'nombre' => 'Triple viral (SRP)', 'dosis' => '1ª dosis', 'edad_meses' => 12],
        ['clave' => 'neumococo_refuerzo', 'nombre' => 'Neumococo conjugada', 'dosis' => 'Refuerzo', 'edad_meses' => 12],
        ['clave' => 'hepatitis_a', 'nombre' => 'Hepatitis A', 'dosis' => 'Única', 'edad_meses' => 12],
        ['clave' => 'cuadruple_refuerzo_15m', 'nombre' => 'Cuádruple bacteriana', 'dosis' => 'Refuerzo', 'edad_meses' => 15],
        ['clave' => 'varicela', 'nombre' => 'Varicela', 'dosis' => 'Única', 'edad_meses' => 15],
        ['clave' => 'triple_bacteriana_refuerzo_18m', 'nombre' => 'Triple bacteriana (DTP)', 'dosis' => 'Refuerzo', 'edad_meses' => 18],
        ['clave' => 'opv_refuerzo_18m', 'nombre' => 'OPV (polio oral)', 'dosis' => 'Refuerzo', 'edad_meses' => 18],
    ],
];
