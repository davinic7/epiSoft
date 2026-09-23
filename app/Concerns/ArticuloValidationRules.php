<?php

namespace App\Concerns;

use App\Enums\CategoriaArticulo;
use App\Models\Articulo;
use App\Support\InstitucionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ArticuloValidationRules
{
    /**
     * Get the validation rules used to validate artículos.
     *
     * El nombre es único por institución; Rule::unique() consulta la tabla
     * directamente y no aplica el global scope del modelo (ver
     * InstitucionScope), así que la institución activa se agrega a mano.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function articuloRules(?int $articuloId = null): array
    {
        $unico = Rule::unique(Articulo::class)
            ->where('institucion_id', app(InstitucionContext::class)->id());

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                $articuloId === null ? $unico : $unico->ignore($articuloId),
            ],
            'categoria' => ['required', Rule::enum(CategoriaArticulo::class)],
            'unidadMedida' => ['required', 'string', 'max:50'],
            'stockMinimo' => ['required', 'numeric', 'min:0'],
        ];
    }
}
