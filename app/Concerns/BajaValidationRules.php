<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait BajaValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function bajaRules(): array
    {
        return [
            'motivoBaja' => ['required', 'string', 'max:1000'],
            'fechaBaja' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
