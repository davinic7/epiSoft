<?php

namespace App\Concerns;

use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait NinoValidationRules
{
    /**
     * Reglas del paso 1 (identificación). El DNI es único por institución;
     * Rule::unique() consulta la tabla directamente y no aplica el global
     * scope del modelo (ver InstitucionScope), así que la institución
     * activa se agrega a mano.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function reglasPasoUno(?int $ninoId = null): array
    {
        $dniUnico = Rule::unique(Nino::class)
            ->where('institucion_id', app(InstitucionContext::class)->id());

        return [
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'dni' => [
                'required',
                'string',
                'max:20',
                $ninoId === null ? $dniUnico : $dniUnico->ignore($ninoId),
            ],
            'fechaNacimiento' => ['required', 'date', 'before:today'],
            'lugarNacimiento' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Reglas del paso 2 (domicilio).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function reglasPasoDos(): array
    {
        return [
            'domicilio' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Reglas del paso 3 (datos institucionales). La sala, si se elige, tiene
     * que pertenecer a la institución activa.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function reglasPasoTres(): array
    {
        return [
            'salaId' => [
                'nullable',
                Rule::exists(Sala::class, 'id')->where('institucion_id', app(InstitucionContext::class)->id()),
            ],
            'fechaIngreso' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function reglasPaso(int $paso, ?int $ninoId = null): array
    {
        return match ($paso) {
            1 => $this->reglasPasoUno($ninoId),
            2 => $this->reglasPasoDos(),
            3 => $this->reglasPasoTres(),
            default => [],
        };
    }

    /**
     * Todas las reglas, usadas al guardar sin importar en qué paso quedó el
     * usuario (defensa en profundidad además de la validación por paso).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function ninoRules(?int $ninoId = null): array
    {
        return [
            ...$this->reglasPasoUno($ninoId),
            ...$this->reglasPasoDos(),
            ...$this->reglasPasoTres(),
        ];
    }
}
