<?php

namespace App\Livewire\Ninos;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Asistencia;
use App\Models\Nino;
use App\Models\Sala;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Reporte mensual de asistencia: por niño (día a día) o por sala (total de
 * días presente de cada niño en el mes).
 */
#[Title('Reporte de asistencia')]
class ReporteAsistencia extends Component
{
    use AuthorizesRequests;

    public string $modo = 'nino';

    public ?int $ninoId = null;

    public ?int $salaId = null;

    public string $mes;

    public function mount(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $this->mes = Carbon::today()->format('Y-m');
    }

    /**
     * @return EloquentCollection<int, Nino>
     */
    #[Computed]
    public function ninos(): EloquentCollection
    {
        return Nino::query()->orderBy('apellidos')->orderBy('nombres')->get();
    }

    /**
     * @return EloquentCollection<int, Sala>
     */
    #[Computed]
    public function salas(): EloquentCollection
    {
        return Sala::query()->orderBy('nombre')->get();
    }

    /**
     * @return ?Collection<int, array{fecha: Carbon, estado: string, hora_ingreso: ?string, hora_egreso: ?string}>
     */
    #[Computed]
    public function reportePorNino(): ?Collection
    {
        if ($this->modo !== 'nino' || $this->ninoId === null) {
            return null;
        }

        [$inicio, $fin] = $this->rangoDelMes();

        $registros = Asistencia::query()
            ->where('nino_id', $this->ninoId)
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
     * @return ?Collection<int, array{nino: Nino, dias_presente: int, dias_registrados: int}>
     */
    #[Computed]
    public function reportePorSala(): ?Collection
    {
        if ($this->modo !== 'sala' || $this->salaId === null) {
            return null;
        }

        [$inicio, $fin] = $this->rangoDelMes();

        $ninos = Nino::query()
            ->where('sala_id', $this->salaId)
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $filas = [];

        foreach ($ninos as $nino) {
            $registros = $nino->asistencias()->whereBetween('fecha', [$inicio, $fin])->get();

            $filas[] = [
                'nino' => $nino,
                'dias_presente' => (int) $registros->where('presente', true)->count(),
                'dias_registrados' => (int) $registros->count(),
            ];
        }

        return collect($filas);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoDelMes(): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d', $this->mes.'-01')->startOfMonth();

        return [$inicio, $inicio->clone()->endOfMonth()];
    }

    public function render(): View
    {
        return view('livewire.ninos.reporte-asistencia');
    }
}
