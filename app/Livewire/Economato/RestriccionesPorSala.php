<?php

namespace App\Livewire\Economato;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Models\Sala;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listado imprimible de alergias y restricciones alimentarias por sala,
 * para que el personal de cocina lo tenga a mano sin necesitar acceso al
 * legajo completo (que exige ninos.ver, un permiso que este rol no tiene:
 * ver la matriz de ProvisionadorDeRolesInstitucionales). Solo expone lo
 * mínimo necesario para no servir un alimento peligroso, no el resto del
 * legajo.
 *
 * Ya existe una nómina equivalente en App\Livewire\Salas\Ver, pero esa
 * pantalla exige ninos.ver.
 */
#[Title('Restricciones por sala')]
class RestriccionesPorSala extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));

        Nino::query()->whereHas('alergias')->each(fn (Nino $nino) => $nino->registrarAcceso());
    }

    /**
     * @return Collection<int, Sala>
     */
    #[Computed]
    public function salas(): Collection
    {
        return Sala::query()->with('ninos.alergias')->orderBy('nombre')->get();
    }

    /**
     * Niños de una sala con al menos una alergia o restricción cargada,
     * ordenados por apellido y nombre.
     *
     * @return Collection<int, Nino>
     */
    public function ninosConAlergias(Sala $sala): Collection
    {
        return $sala->ninos
            ->filter(fn (Nino $nino): bool => $nino->alergias->isNotEmpty())
            ->sortBy([['apellidos', 'asc'], ['nombres', 'asc']])
            ->values();
    }

    public function render(): View
    {
        return view('livewire.economato.restricciones-por-sala');
    }
}
