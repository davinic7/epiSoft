<?php

namespace App\Concerns;

use App\Models\Nino;
use App\Support\InstitucionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait NinoValidationRules
{
    /**
     * Get the validation rules used to validate a child's file. The DNI only
     * has to be unique within the active institution.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function ninoRules(?int $ninoId = null): array
    {
        $dniUnico = Rule::unique(Nino::class)
            ->where('institucion_id', app(InstitucionContext::class)->id())
            ->withoutTrashed();

        return [
            'apellido' => ['required', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'dni' => [
                'required',
                'regex:/^\d{7,8}$/',
                $ninoId === null ? $dniUnico : $dniUnico->ignore($ninoId),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'domicilio' => ['nullable', 'string', 'max:255'],
        ];
    }
}
