<?php

namespace App\Concerns;

use App\Models\Articulo;
use App\Support\InstitucionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait MovimientoDeStockValidationRules
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
            'origen' => ['required', 'string', 'max:255'],
            'contraparte' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get the validation rules used to registrar una salida de stock. La
     * comprobación de que no supere el stock disponible se hace aparte
     * (depende de los lotes vigentes al momento de guardar, no es una regla
     * estática de formulario).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function salidaRules(): array
    {
        return [
            'articuloId' => [
                'required',
                Rule::exists(Articulo::class, 'id')->where('institucion_id', app(InstitucionContext::class)->id()),
            ],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'origen' => ['required', 'string', 'max:255'],
            'contraparte' => ['required', 'string', 'max:255'],
        ];
    }
}
