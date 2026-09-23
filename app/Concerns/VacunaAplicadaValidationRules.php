<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait VacunaAplicadaValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function vacunaAplicadaRules(): array
    {
        return [
            'vacunaClave' => [
                'required',
                'string',
                Rule::in(array_column(config('calendario_vacunacion.vacunas'), 'clave')),
            ],
            'fechaAplicacion' => ['required', 'date', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
