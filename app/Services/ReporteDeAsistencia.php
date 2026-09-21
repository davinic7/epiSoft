<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Nino;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Asistencia mensual de un niño, día a día, contra el calendario real del
 * mes (incluye los días sin registro). Usado tanto por el reporte mensual
 * (App\Livewire\Ninos\ReporteAsistencia) como por la pestaña de asistencia
 * del legajo (App\Livewire\Ninos\Legajo).
 */
class ReporteDeAsistencia
{
    /**
     * @return Collection<int, array{fecha: Carbon, estado: string, hora_ingreso: ?string, hora_egreso: ?string}>
     */
    public function porNino(Nino $nino, Carbon $mes): Collection
    {
        $inicio = $mes->clone()->startOfMonth();
        $fin = $mes->clone()->endOfMonth();

        $registros = $nino->asistencias()
            ->whereBetween('fecha', [$inicio, $fin])
            ->get()
            ->keyBy(fn (Asistencia $registro) => $registro->fecha->format('Y-m-d'));

        $dias = [];

        foreach (CarbonPeriod::create($inicio, $fin) as $dia) {
            /** @var Carbon $dia */
            $registro = $registros->get($dia->format('Y-m-d'));

            $dias[] = [
                'fecha' => $dia,
                'estado' => $registro === null ? 'sin_registro' : ($registro->presente ? 'presente' : 'ausente'),
                'hora_ingreso' => $registro?->hora_ingreso,
                'hora_egreso' => $registro?->hora_egreso,
            ];
        }

        return collect($dias);
    }

    /**
     * @return array{dias_presente: int, dias_registrados: int}
     */
    public function totalesPorNino(Nino $nino, Carbon $mes): array
    {
        $registros = $nino->asistencias()
            ->whereBetween('fecha', [$mes->clone()->startOfMonth(), $mes->clone()->endOfMonth()])
            ->get();

        return [
            'dias_presente' => (int) $registros->where('presente', true)->count(),
            'dias_registrados' => (int) $registros->count(),
        ];
    }
}
