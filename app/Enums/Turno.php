<?php

namespace App\Enums;

/**
 * La Dir. Prov. de Primera Infancia define que los EPI atiendan de lunes a
 * viernes en dos turnos (guía, sección "Horarios de funcionamiento y
 * atención"): mañana y tarde. Una sala existe en uno de los dos; la misma
 * aula física en turnos distintos se modela como dos salas separadas, cada
 * una con su propio cupo.
 */
enum Turno: string
{
    case Manana = 'mañana';
    case Tarde = 'tarde';
}
