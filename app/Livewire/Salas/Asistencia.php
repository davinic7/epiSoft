<?php

namespace App\Livewire\Salas;

use App\Concerns\AsistenciaValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Asistencia as AsistenciaModelo;
use App\Models\Nino;
use App\Models\Referente;
use App\Models\Sala;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Toma de asistencia de una sala en un día: presente/ausente, horario de
 * ingreso y egreso y quién retiró a cada niño. El encargado de recepción,
 * que solo tiene ninos.crear, puede tomarla: "registra por escrito toda
 * novedad, incluido el horario de ingreso y egreso" (guía, cap. 2.2, Anexo
 * 1 Ficha 1) — igual que con los referentes (ver Ninos\Referentes).
 */
#[Title('Asistencia')]
class Asistencia extends Component
{
    use AsistenciaValidationRules, AuthorizesRequests;

    public int $salaId;

    public string $fecha;

    public bool $soloLectura = false;

    /**
     * @var array<int, array{presente: bool, horaIngreso: string, horaEgreso: string, retiradoPorId: ?int, observaciones: string}>
     */
    public array $filas = [];

    public function mount(int $sala): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $this->salaId = Sala::findOrFail($sala)->id;
        $this->fecha = Carbon::today()->format('Y-m-d');
        $this->soloLectura = ! Gate::any([
            Modulo::Ninos->permiso(AccionPermiso::Crear),
            Modulo::Ninos->permiso(AccionPermiso::Editar),
        ]);

        $this->cargarFilas();
    }

    public function updatedFecha(): void
    {
        $this->cargarFilas();
    }

    private function cargarFilas(): void
    {
        $existentes = AsistenciaModelo::query()
            ->where('sala_id', $this->salaId)
            ->whereDate('fecha', $this->fecha)
            ->get()
            ->keyBy('nino_id');

        $this->filas = $this->ninos()
            ->mapWithKeys(function (Nino $nino) use ($existentes) {
                $registro = $existentes->get($nino->id);

                if ($registro === null) {
                    return [$nino->id => [
                        'presente' => true,
                        'horaIngreso' => '',
                        'horaEgreso' => '',
                        'retiradoPorId' => null,
                        'observaciones' => '',
                    ]];
                }

                return [$nino->id => [
                    'presente' => $registro->presente,
                    'horaIngreso' => (string) $registro->hora_ingreso,
                    'horaEgreso' => (string) $registro->hora_egreso,
                    'retiradoPorId' => $registro->retirado_por_id,
                    'observaciones' => (string) $registro->observaciones,
                ]];
            })
            ->all();
    }

    #[Computed]
    public function sala(): Sala
    {
        return Sala::findOrFail($this->salaId);
    }

    /**
     * @return Collection<int, Nino>
     */
    #[Computed]
    public function ninos(): Collection
    {
        return Nino::query()
            ->where('sala_id', $this->salaId)
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Referentes de un niño autorizados a retirarlo, para ofrecer solo esas
     * opciones en "retirado por".
     *
     * @return Collection<int, Referente>
     */
    public function referentesAutorizados(int $ninoId): Collection
    {
        $nino = $this->ninos()->firstWhere('id', $ninoId);

        if ($nino === null) {
            return new Collection;
        }

        return $nino->referentes()->wherePivot('autorizado_a_retirar', true)->get();
    }

    public function guardar(): void
    {
        $this->autorizarGestion();

        $fecha = $this->validate(['fecha' => ['required', 'date']])['fecha'];

        foreach ($this->filas as $ninoId => $fila) {
            $datos = validator($fila, $this->filaAsistenciaRules())->validate();

            $retiradoPorId = $fila['retiradoPorId'] ?? null;

            if ($retiradoPorId !== null && ! $this->referentesAutorizados($ninoId)->contains('id', $retiradoPorId)) {
                $this->addError("filas.{$ninoId}.retiradoPorId", __('Ese referente no está autorizado a retirar a este niño.'));

                return;
            }

            // updateOrCreate() compara "fecha" tal cual, sin aplicar el cast
            // a date del modelo, así que una búsqueda directa por string no
            // siempre matchea lo ya guardado; se busca con whereDate() y se
            // arma la fila a mano.
            $registro = AsistenciaModelo::query()
                ->where('nino_id', $ninoId)
                ->whereDate('fecha', $fecha)
                ->first() ?? new AsistenciaModelo(['nino_id' => $ninoId, 'fecha' => $fecha]);

            $registro->fill([
                'sala_id' => $this->salaId,
                'presente' => $datos['presente'],
                'hora_ingreso' => $datos['horaIngreso'] ?: null,
                'hora_egreso' => $datos['horaEgreso'] ?: null,
                'retirado_por_id' => $datos['presente'] ? $retiradoPorId : null,
                'observaciones' => $datos['observaciones'] ?: null,
            ])->save();
        }

        Flux::toast(variant: 'success', text: __('Asistencia guardada.'));
    }

    private function autorizarGestion(): void
    {
        abort_unless(Gate::any([
            Modulo::Ninos->permiso(AccionPermiso::Crear),
            Modulo::Ninos->permiso(AccionPermiso::Editar),
        ]), 403);
    }

    public function render(): View
    {
        return view('livewire.salas.asistencia');
    }
}
