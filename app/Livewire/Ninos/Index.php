<?php

namespace App\Livewire\Ninos;

use App\Concerns\NinoValidationRules;
use App\Models\Nino;
use App\Models\Sala;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Niños')]
class Index extends Component
{
    use AuthorizesRequests, NinoValidationRules, WithPagination;

    public ?int $ninoId = null;

    public string $apellido = '';

    public string $nombre = '';

    public string $dni = '';

    public string $fecha_nacimiento = '';

    public string $domicilio = '';

    /**
     * Filtro del listado por sala: vacío para todas, "sin-sala" para los que
     * todavía no tienen sala o el id de una sala.
     */
    public string $filtroSala = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Nino::class);
    }

    /**
     * @return LengthAwarePaginator<int, Nino>
     */
    #[Computed]
    public function ninos(): LengthAwarePaginator
    {
        return Nino::query()
            ->with('sala')
            ->when($this->filtroSala === 'sin-sala', fn ($query) => $query->whereNull('sala_id'))
            ->when(ctype_digit($this->filtroSala), fn ($query) => $query->where('sala_id', (int) $this->filtroSala))
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15);
    }

    /**
     * @return Collection<int, Sala>
     */
    #[Computed]
    public function salas(): Collection
    {
        return Sala::query()->orderBy('nombre')->get();
    }

    /**
     * Volver a la primera página al cambiar el filtro.
     */
    public function updatedFiltroSala(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the form to create a new child's file.
     */
    public function nuevo(): void
    {
        $this->authorize('create', Nino::class);

        $this->reset(['ninoId', 'apellido', 'nombre', 'dni', 'fecha_nacimiento', 'domicilio']);
        $this->resetErrorBag();
    }

    /**
     * Load a child's file into the form for editing.
     */
    public function editar(int $ninoId): void
    {
        $nino = Nino::findOrFail($ninoId);

        $this->authorize('update', $nino);

        $this->ninoId = $nino->id;
        $this->apellido = $nino->apellido;
        $this->nombre = $nino->nombre;
        $this->dni = $nino->dni;
        $this->fecha_nacimiento = $nino->fecha_nacimiento->format('Y-m-d');
        $this->domicilio = (string) $nino->domicilio;
        $this->resetErrorBag();
    }

    /**
     * Create or update the child's file currently loaded in the form.
     */
    public function guardar(): void
    {
        $nino = $this->ninoId ? Nino::findOrFail($this->ninoId) : null;

        $this->authorize($nino ? 'update' : 'create', $nino ?? Nino::class);

        $datos = $this->validate($this->ninoRules($this->ninoId));

        if ($nino) {
            $nino->update($datos);
        } else {
            Nino::create($datos);
        }

        $this->js("\$flux.modal('formulario-nino').close()");

        Flux::toast(variant: 'success', text: __('Legajo guardado.'));
    }

    /**
     * Delete the given child's file.
     */
    public function eliminar(int $ninoId): void
    {
        $nino = Nino::findOrFail($ninoId);

        $this->authorize('delete', $nino);

        $nino->delete();

        Flux::toast(variant: 'success', text: __('Legajo eliminado.'));
    }

    public function render(): View
    {
        return view('livewire.ninos.index');
    }
}
