<?php

namespace App\Enums;

/**
 * Momento de alimentación de la jornada (guía, Anexo 1, Ficha 5 "Personal
 * de cocina", pág. 61: "desayuno, almuerzo, colación, merienda
 * reforzada").
 */
enum MomentoComida: string
{
    case Desayuno = 'desayuno';
    case Colacion = 'colación';
    case Almuerzo = 'almuerzo';
    case MeriendaReforzada = 'merienda reforzada';
}
