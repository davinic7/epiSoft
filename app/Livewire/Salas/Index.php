<?php

namespace App\Livewire\Salas;

use App\Concerns\SalaValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
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
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));
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
     * @return array<int, Turno>
     */
    #[Computed]
    public function turnos(): array
    {
        return Turno::cases();
    }

    /**
     * Reset the form to create a new sala.
     */
    public function nuevo(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Crear));

        $this->reset(['salaId', 'nombre', 'turno', 'capacidad']);
        $this->resetErrorBag();
    }

    /**
     * Load a sala into the form for editing.
     */
    public function editar(int $salaId): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Editar));

        $sala = Sala::findOrFail($salaId);

        $this->salaId = $sala->id;
        $this->nombre = $sala->nombre;
        $this->turno = $sala->turno->value;
        $this->capacidad = $sala->capacidad;
        $this->resetErrorBag();
    }

    /**
     * Create or update the sala currently loaded in the form.
     */
    public function guardar(): void
    {
        $this->authorize(Modulo::Ninos->permiso($this->salaId ? AccionPermiso::Editar : AccionPermiso::Crear));

        $datos = $this->validate($this->salaRules($this->salaId));

        if ($this->salaId) {
            Sala::findOrFail($this->salaId)->update($datos);
        } else {
            Sala::create($datos);
        }

        $this->js("\$flux.modal('formulario-sala').close()");

        Flux::toast(variant: 'success', text: __('Sala guardada.'));
    }

    /**
     * Delete the given sala.
     */
    public function eliminar(int $salaId): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Eliminar));

        Sala::findOrFail($salaId)->delete();

        Flux::toast(variant: 'success', text: __('Sala eliminada.'));
    }

    public function render(): View
    {
        return view('livewire.salas.index');
    }
}
