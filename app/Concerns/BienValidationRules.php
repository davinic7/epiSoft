<?php

namespace App\Concerns;

use App\Enums\EstadoConservacion;
use App\Models\Bien;
use App\Support\InstitucionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait BienValidationRules
{
    /**
     * Get the validation rules used to validate un bien patrimonial.
     *
     * El código es único por institución; Rule::unique() consulta la
     * tabla directamente y no aplica el global scope del modelo (ver
     * InstitucionScope), así que la institución activa se agrega a mano.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function bienRules(?int $bienId = null): array
    {
        $unico = Rule::unique(Bien::class, 'codigo')
            ->where('institucion_id', app(InstitucionContext::class)->id());

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => [
                'required',
                'string',
                'max:50',
                $bienId === null ? $unico : $unico->ignore($bienId),
            ],
            'ubicacion' => ['required', 'string', 'max:255'],
            'estadoConservacion' => ['required', Rule::enum(EstadoConservacion::class)],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function movimientoDeUbicacionRules(): array
    {
        return [
            'ubicacionNueva' => ['required', 'string', 'max:255'],
            'fechaMovimiento' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
