<?php

namespace App\Enums;

/**
 * Día de la semana en que el EPI atiende (ver App\Enums\Turno: "la Dir.
 * Prov. de Primera Infancia define que los EPI atiendan de lunes a
 * viernes").
 */
enum DiaSemana: string
{
    case Lunes = 'lunes';
    case Martes = 'martes';
    case Miercoles = 'miércoles';
    case Jueves = 'jueves';
    case Viernes = 'viernes';
}
