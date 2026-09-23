<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait ReferenteValidationRules
{
    /**
     * Reglas de los datos propios del referente. Sin regla de unicidad en
     * el DNI a propósito: vincular un referente a un niño reutiliza el
     * registro existente por DNI dentro de la institución (ver
     * Referentes::agregar()) en vez de rechazarlo como duplicado.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function referenteRules(): array
    {
        return [
            'dni' => ['required', 'string', 'max:20'],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'domicilio' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Reglas del vínculo entre un niño y un referente (tabla intermedia).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function vinculoRules(): array
    {
        return [
            'parentesco' => ['required', 'string', 'max:255'],
            'autorizadoARetirar' => ['boolean'],
        ];
    }
}
