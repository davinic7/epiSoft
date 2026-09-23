<?php

namespace App\Enums;

/**
 * Categorías del catálogo de artículos de economato. Alimentos, limpieza y
 * librería vienen del "libro de registro de economato" (guía, cap. 3.4,
 * pág. 23); pedagogía cubre los materiales e insumos didácticos que ese
 * libro no nombra pero que el encargado de economato también gestiona
 * (guía, cap. 2.2 y Anexo 1, Ficha 4, pág. 15, 60).
 */
enum CategoriaArticulo: string
{
    case Alimentos = 'alimentos';
    case Limpieza = 'limpieza';
    case Libreria = 'librería';
    case Pedagogia = 'pedagogía';
}
