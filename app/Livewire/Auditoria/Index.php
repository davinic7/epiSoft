<?php

namespace App\Livewire\Auditoria;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Auditoría')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public string $evento = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize(Modulo::Auditoria->permiso(AccionPermiso::Ver));
    }

    public function updatingEvento(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    #[Computed]
    public function registros(): LengthAwarePaginator
    {
        return Audit::query()
            ->with('usuario')
            ->when($this->evento !== '', fn ($consulta) => $consulta->where('event', $this->evento))
            ->latest()
            ->latest('id')
            ->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.auditoria.index');
    }
}
