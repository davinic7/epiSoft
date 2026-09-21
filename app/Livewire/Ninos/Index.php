<?php

namespace App\Livewire\Ninos;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Niños')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public function mount(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));
    }

    /**
     * @return LengthAwarePaginator<int, Nino>
     */
    #[Computed]
    public function ninos(): LengthAwarePaginator
    {
        return Nino::query()
            ->with('sala')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.ninos.index');
    }
}
