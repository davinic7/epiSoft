<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait AsistenciaValidationRules
{
    /**
     * Reglas de una fila de asistencia (un niño, un día). El "retirado por"
     * se valida aparte (App\Livewire\Salas\Asistencia::guardar()) porque
     * las opciones válidas dependen de los referentes autorizados de cada
     * niño, no de una lista fija.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function filaAsistenciaRules(): array
    {
        return [
            'presente' => ['boolean'],
            'horaIngreso' => ['nullable', 'date_format:H:i'],
            'horaEgreso' => ['nullable', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
