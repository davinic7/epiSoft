<?php

namespace App\Concerns;

use App\Enums\SeveridadAlergia;
use App\Enums\TipoAlergia;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AlergiaValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function alergiaRules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoAlergia::class)],
            'severidad' => ['required', Rule::enum(SeveridadAlergia::class)],
            'descripcion' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
