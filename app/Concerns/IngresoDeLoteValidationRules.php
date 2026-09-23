<?php

namespace App\Concerns;

use App\Models\Articulo;
use App\Support\InstitucionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait IngresoDeLoteValidationRules
{
    /**
     * Get the validation rules used to registrar un ingreso de stock.
     *
     * Rule::exists() consulta la tabla directamente y no aplica el global
     * scope del modelo (ver InstitucionScope), así que la institución
     * activa se agrega a mano para no aceptar un artículo de otra
     * institución.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function ingresoRules(): array
    {
        return [
            'articuloId' => [
                'required',
                Rule::exists(Articulo::class, 'id')->where('institucion_id', app(InstitucionContext::class)->id()),
            ],
            'fechaVencimiento' => ['required', 'date'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
