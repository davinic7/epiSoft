<?php

namespace App\Livewire\Ninos;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Services\CalendarioDeVacunacion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Vacunas atrasadas')]
class VacunasAtrasadas extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));
    }

    /**
     * @return Collection<int, Nino>
     */
    #[Computed]
    public function ninos(): Collection
    {
        $calendario = app(CalendarioDeVacunacion::class);

        return Nino::query()
            ->with('vacunasAplicadas')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get()
            ->filter(fn (Nino $nino) => $calendario->tieneAtrasadas($nino))
            ->values();
    }

    public function render(): View
    {
        return view('livewire.ninos.vacunas-atrasadas');
    }
}
