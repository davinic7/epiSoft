<?php

namespace App\Livewire\Salas;

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
 * Nómina de niños de una sala, con sus alergias y restricciones
 * alimentarias destacadas (guía, cap. 3.4: listados por sala visibles
 * para el personal, ver también Nino::alergias()).
 */
#[Title('Sala')]
class Ver extends Component
{
    use AuthorizesRequests;

    public int $salaId;

    public function mount(int $sala): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $this->salaId = Sala::findOrFail($sala)->id;
    }

    #[Computed]
    public function sala(): Sala
    {
        return Sala::findOrFail($this->salaId);
    }

    /**
     * @return Collection<int, Nino>
     */
    #[Computed]
    public function ninos(): Collection
    {
        return Nino::query()
            ->where('sala_id', $this->salaId)
            ->with('alergias')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.salas.ver');
    }
}
