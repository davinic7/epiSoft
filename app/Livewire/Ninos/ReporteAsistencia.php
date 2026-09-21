<?php

namespace App\Livewire\Ninos;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Models\Sala;
use App\Services\ReporteDeAsistencia;
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

        $nino = Nino::findOrFail($this->ninoId);

        return app(ReporteDeAsistencia::class)->porNino($nino, $this->rangoDelMes()[0]);
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

        $mes = $this->rangoDelMes()[0];
        $servicio = app(ReporteDeAsistencia::class);

        $ninos = Nino::query()
            ->where('sala_id', $this->salaId)
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $filas = [];

        foreach ($ninos as $nino) {
            $filas[] = ['nino' => $nino, ...$servicio->totalesPorNino($nino, $mes)];
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
