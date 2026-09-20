<?php

namespace App\Enums;

/**
 * Turno en el que funciona una sala.
 */
enum Turno: string
{
    case Manana = 'manana';
    case Tarde = 'tarde';
    case Completo = 'completo';

    /**
     * Nombre en español del turno, para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Manana => __('Mañana'),
            self::Tarde => __('Tarde'),
            self::Completo => __('Jornada completa'),
        };
    }
}
