<?php

namespace App\Concerns;

use App\Models\Institucion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait InstitucionValidationRules
{
    /**
     * Get the validation rules used to validate institutions.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function institucionRules(?int $institucionId = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'cuit' => [
                'required',
                'string',
                'max:20',
                $institucionId === null
                    ? Rule::unique(Institucion::class)
                    : Rule::unique(Institucion::class)->ignore($institucionId),
            ],
            'referente' => ['required', 'string', 'max:255'],
            'capacidad' => ['required', 'integer', 'min:1'],
        ];
    }
}
