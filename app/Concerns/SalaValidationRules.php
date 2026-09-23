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
     * Get the validation rules used to validate salas.
     *
     * El nombre es único por institución y turno; Rule::unique() consulta la
     * tabla directamente y no aplica el global scope del modelo (ver
     * InstitucionScope), así que la institución activa se agrega a mano.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function salaRules(?int $salaId = null): array
    {
        $unico = Rule::unique(Sala::class)
            ->where('turno', $this->turno)
            ->where('institucion_id', app(InstitucionContext::class)->id());

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                $salaId === null ? $unico : $unico->ignore($salaId),
            ],
            'turno' => ['required', Rule::enum(Turno::class)],
            'capacidad' => ['required', 'integer', 'min:1'],
        ];
    }
}
