<?php

namespace App\Enums;

/**
 * Estado del circuito de aprobación de un menú semanal. Un menú aprobado
 * ya no se edita (ver App\Livewire\Economato\MenuEditor).
 */
enum EstadoMenu: string
{
    case Borrador = 'borrador';
    case Aprobado = 'aprobado';
}
