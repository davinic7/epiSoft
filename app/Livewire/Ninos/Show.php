<?php

namespace App\Livewire\Ninos;

use App\Models\Nino;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Legajo')]
class Show extends Component
{
    use AuthorizesRequests;

    public Nino $nino;

    /**
     * Mount the component. Abrir un legajo deja constancia en la auditoría.
     */
    public function mount(Nino $nino): void
    {
        $this->authorize('view', $nino);

        $this->nino = $nino;
        $nino->registrarAcceso();
    }

    public function render(): View
    {
        return view('livewire.ninos.show');
    }
}
