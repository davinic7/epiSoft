<?php

namespace App\Livewire\Salas;

use App\Concerns\SalaValidationRules;
use App\Enums\Turno;
use App\Models\Sala;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Salas')]
class Index extends Component
{
    use AuthorizesRequests, SalaValidationRules, WithPagination;

    public ?int $salaId = null;

    public string $nombre = '';

    public string $turno = '';

    public ?int $capacidad = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Sala::class);
    }

    /**
     * @return LengthAwarePaginator<int, Sala>
     */
    #[Computed]
    public function salas(): LengthAwarePaginator
    {
        return Sala::query()->orderBy('nombre')->paginate(10);
    }

    /**
     * Reset the form to create a new room.
     */
    public function nuevo(): void
    {
        $this->authorize('create', Sala::class);

        $this->reset(['salaId', 'nombre', 'turno', 'capacidad']);
        $this->resetErrorBag();
    }

    /**
     * Load a room into the form for editing.
     */
    public function editar(int $salaId): void
    {
        $sala = Sala::findOrFail($salaId);

        $this->authorize('update', $sala);

        $this->salaId = $sala->id;
        $this->nombre = $sala->nombre;
        $this->turno = $sala->turno->value;
        $this->capacidad = $sala->capacidad;
        $this->resetErrorBag();
    }

    /**
     * Create or update the room currently loaded in the form.
     */
    public function guardar(): void
    {
        $sala = $this->salaId ? Sala::findOrFail($this->salaId) : null;

        $this->authorize($sala ? 'update' : 'create', $sala ?? Sala::class);

        $datos = $this->validate($this->salaRules($this->salaId));

        if ($sala) {
            $sala->update($datos);
        } else {
            Sala::create($datos);
        }

        $this->js("\$flux.modal('formulario-sala').close()");

        Flux::toast(variant: 'success', text: __('Sala guardada.'));
    }

    /**
     * Delete the given room.
     */
    public function eliminar(int $salaId): void
    {
        $sala = Sala::findOrFail($salaId);

        $this->authorize('delete', $sala);

        $sala->delete();

        Flux::toast(variant: 'success', text: __('Sala eliminada.'));
    }

    public function render(): View
    {
        return view('livewire.salas.index', ['turnos' => Turno::cases()]);
    }
}
