<?php

namespace App\Concerns;

use App\Models\MenuSemanal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

trait MenuSemanalValidationRules
{
    /**
     * Get the validation rules used to crear un menú semanal.
     *
     * No usa Rule::unique(): "semana_inicio" es una columna 'date', y
     * Eloquent la guarda con hora (00:00:00) aunque solo se lea la fecha,
     * así que una comparación exacta por string ("Y-m-d") no matchea lo
     * guardado (mismo motivo por el que Salas\Asistencia usa whereDate()).
     * MenuSemanal::query() ya aplica el global scope de institución (ver
     * InstitucionScope), así que no hace falta agregarlo a mano.
     *
     * @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>>
     */
    protected function menuSemanalRules(): array
    {
        return [
            'semanaInicio' => [
                'required',
                'date',
                function (string $atributo, mixed $valor, Closure $falla): void {
                    if (! Carbon::parse((string) $valor)->isMonday()) {
                        $falla(__('La semana debe empezar un lunes.'));
                    }
                },
                function (string $atributo, mixed $valor, Closure $falla): void {
                    if (MenuSemanal::query()->whereDate('semana_inicio', (string) $valor)->exists()) {
                        $falla(__('Ya existe un menú para esa semana.'));
                    }
                },
            ],
        ];
    }
}
