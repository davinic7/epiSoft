<?php

namespace App\Livewire\Instituciones;

use App\Concerns\InstitucionValidationRules;
use App\Models\Institucion;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Instituciones')]
class Index extends Component
{
    use AuthorizesRequests, InstitucionValidationRules, WithPagination;

    public ?int $institucionId = null;

    public string $nombre = '';

    public string $direccion = '';

    public string $cuit = '';

    public string $referente = '';

    public ?int $capacidad = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Institucion::class);
    }

    /**
     * @return LengthAwarePaginator<int, Institucion>
     */
    #[Computed]
    public function instituciones(): LengthAwarePaginator
    {
        return Institucion::query()->orderBy('nombre')->paginate(10);
    }

    /**
     * Reset the form to create a new institution.
     */
    public function nuevo(): void
    {
        $this->authorize('create', Institucion::class);

        $this->reset(['institucionId', 'nombre', 'direccion', 'cuit', 'referente', 'capacidad']);
        $this->resetErrorBag();
    }

    /**
     * Load an institution into the form for editing.
     */
    public function editar(int $institucionId): void
    {
        $institucion = Institucion::findOrFail($institucionId);

        $this->authorize('update', $institucion);

        $this->institucionId = $institucion->id;
        $this->nombre = $institucion->nombre;
        $this->direccion = (string) $institucion->direccion;
        $this->cuit = (string) $institucion->cuit;
        $this->referente = (string) $institucion->referente;
        $this->capacidad = $institucion->capacidad;
        $this->resetErrorBag();
    }

    /**
     * Create or update the institution currently loaded in the form.
     */
    public function guardar(): void
    {
        $institucion = $this->institucionId ? Institucion::findOrFail($this->institucionId) : null;

        $this->authorize($institucion ? 'update' : 'create', $institucion ?? Institucion::class);

        $datos = $this->validate($this->institucionRules($this->institucionId));

        if ($institucion) {
            $institucion->update($datos);
        } else {
            Institucion::create($datos);
        }

        $this->js("\$flux.modal('formulario-institucion').close()");

        Flux::toast(variant: 'success', text: __('Institución guardada.'));
    }

    /**
     * Delete the given institution.
     */
    public function eliminar(int $institucionId): void
    {
        $institucion = Institucion::findOrFail($institucionId);

        $this->authorize('delete', $institucion);

        $institucion->delete();

        Flux::toast(variant: 'success', text: __('Institución eliminada.'));
    }

    public function render(): View
    {
        return view('livewire.instituciones.index');
    }
}
