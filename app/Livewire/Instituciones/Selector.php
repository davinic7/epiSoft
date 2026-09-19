<?php

namespace App\Livewire\Instituciones;

use App\Models\Institucion;
use App\Support\InstitucionContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Selector extends Component
{
    /**
     * @return Collection<int, Institucion>
     */
    #[Computed]
    public function instituciones(): Collection
    {
        return auth()->user()->institucionesAccesibles()->orderBy('nombre')->get(['instituciones.id', 'instituciones.nombre']);
    }

    #[Computed]
    public function activa(): ?int
    {
        return app(InstitucionContext::class)->id();
    }

    /**
     * Cambia la institución activa y recarga la página para que todo lo
     * que se ve quede filtrado por la nueva.
     */
    public function cambiar(int $institucionId): void
    {
        abort_unless(
            auth()->user()->institucionesAccesibles()->whereKey($institucionId)->exists(),
            403,
        );

        app(InstitucionContext::class)->set($institucionId);

        $this->js('window.location.reload()');
    }

    public function render(): View
    {
        return view('livewire.instituciones.selector');
    }
}
