<?php

namespace App\Concerns;

use App\Enums\Turno;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait SalaValidationRules
{
    /**
     * Get the validation rules used to validate rooms. The name only has to
     * be unique within the active institution.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function salaRules(?int $salaId = null): array
    {
        $nombreUnico = Rule::unique(Sala::class)
            ->where('institucion_id', app(InstitucionContext::class)->id())
            ->withoutTrashed();

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                $salaId === null ? $nombreUnico : $nombreUnico->ignore($salaId),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'turno' => ['required', Rule::enum(Turno::class)],
            'capacidad' => ['required', 'integer', 'min:1', 'max:200'],
        ];
    }
}
