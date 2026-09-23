<?php

namespace App\Livewire\Economato;

use App\Concerns\ArticuloValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\CategoriaArticulo;
use App\Enums\Modulo;
use App\Models\Articulo;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Economato')]
class Index extends Component
{
    use ArticuloValidationRules, AuthorizesRequests, WithPagination;

    public ?int $articuloId = null;

    public string $nombre = '';

    public string $categoria = '';

    public string $unidadMedida = '';

    public string $stockMinimo = '';

    #[Url]
    public string $busqueda = '';

    #[Url]
    public string $filtroCategoria = '';

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroCategoria(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Articulo>
     */
    #[Computed]
    public function articulos(): LengthAwarePaginator
    {
        return Articulo::query()
            ->when($this->busqueda !== '', fn (Builder $query) => $query->where('nombre', 'like', '%'.$this->busqueda.'%'))
            ->when($this->filtroCategoria !== '', fn (Builder $query) => $query->where('categoria', $this->filtroCategoria))
            ->orderBy('nombre')
            ->paginate(15);
    }

    /**
     * @return array<int, CategoriaArticulo>
     */
    #[Computed]
    public function categorias(): array
    {
        return CategoriaArticulo::cases();
    }

    /**
     * Reset the form to create a new artículo.
     */
    public function nuevo(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $this->reset(['articuloId', 'nombre', 'categoria', 'unidadMedida', 'stockMinimo']);
        $this->resetErrorBag();
    }

    /**
     * Load an artículo into the form for editing.
     */
    public function editar(int $articuloId): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        $articulo = Articulo::findOrFail($articuloId);

        $this->articuloId = $articulo->id;
        $this->nombre = $articulo->nombre;
        $this->categoria = $articulo->categoria->value;
        $this->unidadMedida = $articulo->unidad_medida;
        $this->stockMinimo = $articulo->stock_minimo;
        $this->resetErrorBag();
    }

    /**
     * Create or update the artículo currently loaded in the form.
     */
    public function guardar(): void
    {
        $this->authorize(Modulo::Economato->permiso($this->articuloId ? AccionPermiso::Editar : AccionPermiso::Crear));

        $datos = $this->validate($this->articuloRules($this->articuloId));

        $atributos = [
            'nombre' => $datos['nombre'],
            'categoria' => $datos['categoria'],
            'unidad_medida' => $datos['unidadMedida'],
            'stock_minimo' => $datos['stockMinimo'],
        ];

        if ($this->articuloId) {
            Articulo::findOrFail($this->articuloId)->update($atributos);
        } else {
            Articulo::create($atributos);
        }

        $this->js("\$flux.modal('formulario-articulo').close()");

        Flux::toast(variant: 'success', text: __('Artículo guardado.'));
    }

    public function render(): View
    {
        return view('livewire.economato.index');
    }
}
