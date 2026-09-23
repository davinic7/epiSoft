<?php

namespace App\Enums;

/**
 * Estado de conservación de un bien del inventario patrimonial (guía,
 * cap. 3.4, "Libro de registro de inventario institucional", pág. 24).
 */
enum EstadoConservacion: string
{
    case Bueno = 'bueno';
    case Regular = 'regular';
    case Malo = 'malo';
}
