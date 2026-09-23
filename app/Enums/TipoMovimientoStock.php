<?php

namespace App\Enums;

/**
 * Tipo de movimiento sobre un lote de stock. Ver App\Models\Lote::stockActual():
 * el stock de un lote nunca se guarda como campo editable, se calcula
 * sumando entradas y restando salidas.
 */
enum TipoMovimientoStock: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';
}
