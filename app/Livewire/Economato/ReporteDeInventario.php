<?php

namespace App\Livewire\Economato;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Bien;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Reporte del inventario patrimonial agrupado por ubicación (guía, cap.
 * 3.4, "Libro de registro de inventario institucional", pág. 24).
 */
#[Title('Inventario por ubicación')]
class ReporteDeInventario extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));
    }

    /**
     * @return Collection<string, EloquentCollection<int, Bien>>
     */
    #[Computed]
    public function bienesPorUbicacion(): Collection
    {
        return Bien::query()
            ->orderBy('nombre')
            ->get()
            ->groupBy('ubicacion')
            ->sortKeys();
    }

    public function render(): View
    {
        return view('livewire.economato.reporte-de-inventario');
    }
}
