<?php

namespace App\Livewire\Salas;

use App\Models\Nino;
use App\Models\Sala;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Vista de una sala: sus niños con la edad de cada uno y, para quien puede
 * editar la sala, la asignación de niños sin sala, el pase de sala en grupo
 * y la quita de la sala. La asignación es libre, sin restricción de edad.
 */
#[Title('Sala')]
class Show extends Component
{
    use AuthorizesRequests;

    public Sala $sala;

    /**
     * Niños de esta sala marcados para moverlos o quitarlos.
     *
     * @var list<int|string>
     */
    public array $seleccionados = [];

    public ?int $salaDestinoId = null;

    /**
     * Niños sin sala marcados para agregarlos a esta sala.
     *
     * @var list<int|string>
     */
    public array $paraAgregar = [];

    public string $busqueda = '';

    /**
     * Mount the component.
     */
    public function mount(Sala $sala): void
    {
        $this->authorize('view', $sala);

        $this->sala = $sala;
    }

    /**
     * @return Collection<int, Nino>
     */
    #[Computed]
    public function ninos(): Collection
    {
        return $this->sala->ninos()->orderBy('apellido')->orderBy('nombre')->get();
    }

    /**
     * Niños de la institución que todavía no tienen sala, filtrados por la
     * búsqueda (apellido, nombre o DNI).
     *
     * @return Collection<int, Nino>
     */
    #[Computed]
    public function ninosSinSala(): Collection
    {
        return Nino::query()
            ->whereNull('sala_id')
            ->when($this->busqueda !== '', function ($query) {
                $termino = '%'.$this->busqueda.'%';

                $query->where(fn ($query) => $query
                    ->where('apellido', 'like', $termino)
                    ->orWhere('nombre', 'like', $termino)
                    ->orWhere('dni', 'like', $termino));
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, Sala>
     */
    #[Computed]
    public function otrasSalas(): Collection
    {
        return Sala::query()->whereKeyNot($this->sala->id)->orderBy('nombre')->get();
    }

    /**
     * Rango de edades real de la sala, del más chico al más grande. Es solo
     * informativo: la sala no impone edades.
     */
    #[Computed]
    public function rangoDeEdades(): ?string
    {
        if ($this->ninos->isEmpty()) {
            return null;
        }

        $masChico = $this->ninos->sortByDesc('fecha_nacimiento')->first()->edadLegible();
        $masGrande = $this->ninos->sortBy('fecha_nacimiento')->first()->edadLegible();

        return $masChico === $masGrande
            ? $masChico
            : __('de :desde a :hasta', ['desde' => $masChico, 'hasta' => $masGrande]);
    }

    /**
     * Asigna a esta sala los niños sin sala marcados en el formulario.
     */
    public function agregar(): void
    {
        $this->authorize('update', $this->sala);

        $this->validate(
            ['paraAgregar' => ['required', 'array']],
            ['paraAgregar.required' => __('Marcá al menos un niño para agregar.')],
        );

        Nino::query()
            ->whereNull('sala_id')
            ->whereKey($this->idsDe($this->paraAgregar))
            ->get()
            ->each(fn (Nino $nino) => $nino->sala()->associate($this->sala)->save());

        $this->reset(['paraAgregar', 'busqueda']);
        $this->js("\$flux.modal('agregar-ninos').close()");

        $this->avisarAsignacion($this->sala, __('Niños agregados a la sala.'));
    }

    /**
     * Pase de sala en grupo: mueve los niños marcados a la sala elegida.
     */
    public function mover(): void
    {
        $this->authorize('update', $this->sala);

        $this->validate(
            [
                'seleccionados' => ['required', 'array'],
                'salaDestinoId' => ['required', 'integer'],
            ],
            [
                'seleccionados.required' => __('Marcá al menos un niño para mover.'),
                'salaDestinoId.required' => __('Elegí la sala a la que pasan.'),
            ],
        );

        $destino = Sala::query()->whereKeyNot($this->sala->id)->findOrFail($this->salaDestinoId);

        $this->authorize('update', $destino);

        $this->ninosSeleccionados()->each(fn (Nino $nino) => $nino->sala()->associate($destino)->save());

        $this->reset(['seleccionados', 'salaDestinoId']);

        $this->avisarAsignacion($destino, __('Niños pasados a :sala.', ['sala' => $destino->nombre]));
    }

    /**
     * Quita de la sala a los niños marcados; quedan sin sala asignada.
     */
    public function quitar(): void
    {
        $this->authorize('update', $this->sala);

        $this->validate(
            ['seleccionados' => ['required', 'array']],
            ['seleccionados.required' => __('Marcá al menos un niño para quitar.')],
        );

        $this->ninosSeleccionados()->each(fn (Nino $nino) => $nino->sala()->dissociate()->save());

        $this->reset('seleccionados');

        Flux::toast(variant: 'success', text: __('Niños quitados de la sala.'));
    }

    public function render(): View
    {
        return view('livewire.salas.show');
    }

    /**
     * Niños marcados que efectivamente están en esta sala: un id de otra
     * sala o de otra institución se ignora.
     *
     * @return Collection<int, Nino>
     */
    private function ninosSeleccionados(): Collection
    {
        return $this->sala->ninos()->whereKey($this->idsDe($this->seleccionados))->get();
    }

    /**
     * @param  list<int|string>  $valores
     * @return list<int>
     */
    private function idsDe(array $valores): array
    {
        return array_values(array_map('intval', $valores));
    }

    /**
     * Confirma la asignación y, si la sala quedó por encima de su cupo, lo
     * avisa sin impedirlo.
     */
    private function avisarAsignacion(Sala $sala, string $mensaje): void
    {
        $cantidad = $sala->ninos()->count();

        if ($cantidad > $sala->capacidad) {
            Flux::toast(variant: 'warning', text: __(':mensaje Atención: :sala supera su cupo (:cantidad niños para :capacidad lugares).', [
                'mensaje' => $mensaje,
                'sala' => $sala->nombre,
                'cantidad' => $cantidad,
                'capacidad' => $sala->capacidad,
            ]));

            return;
        }

        Flux::toast(variant: 'success', text: $mensaje);
    }
}
