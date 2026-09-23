<?php

namespace App\Services;

use App\Models\Nino;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Compara el calendario nacional de vacunación
 * (config/calendario_vacunacion.php — ver la advertencia ahí sobre su
 * exactitud) con las vacunas aplicadas de un niño para calcular el
 * estado de cada dosis según su edad.
 */
class CalendarioDeVacunacion
{
    /**
     * @return Collection<int, array{clave: string, nombre: string, dosis: string, edad_meses: int, estado: string, fecha_aplicacion: ?Carbon}>
     */
    public function estadoPorNino(Nino $nino): Collection
    {
        $edadEnMeses = $nino->fecha_nacimiento->diffInMonths(Carbon::today());
        $aplicadas = $nino->vacunasAplicadas->keyBy('vacuna_clave');

        return collect($this->calendario())
            ->map(function (array $dosis) use ($edadEnMeses, $aplicadas) {
                $aplicada = $aplicadas->get($dosis['clave']);

                $estado = match (true) {
                    $aplicada !== null => 'aplicada',
                    $edadEnMeses >= $dosis['edad_meses'] => 'atrasada',
                    default => 'pendiente',
                };

                return [
                    'clave' => $dosis['clave'],
                    'nombre' => $dosis['nombre'],
                    'dosis' => $dosis['dosis'],
                    'edad_meses' => $dosis['edad_meses'],
                    'estado' => $estado,
                    'fecha_aplicacion' => $aplicada?->fecha_aplicacion,
                ];
            })
            ->values();
    }

    public function tieneAtrasadas(Nino $nino): bool
    {
        return $this->estadoPorNino($nino)->contains('estado', 'atrasada');
    }

    /**
     * @return list<array{clave: string, nombre: string, dosis: string, edad_meses: int}>
     */
    private function calendario(): array
    {
        /** @var list<array{clave: string, nombre: string, dosis: string, edad_meses: int}> $vacunas */
        $vacunas = config('calendario_vacunacion.vacunas');

        return $vacunas;
    }
}
