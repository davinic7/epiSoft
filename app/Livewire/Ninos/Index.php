<?php

namespace App\Livewire\Ninos;

use App\Concerns\BajaValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Enums\Turno;
use App\Models\Nino;
use App\Models\Sala;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Niños')]
class Index extends Component
{
    use AuthorizesRequests, BajaValidationRules, WithPagination;

    public ?int $ninoEnBajaId = null;

    public string $motivoBaja = '';

    public string $fechaBaja = '';

    #[Url]
    public string $busqueda = '';

    #[Url]
    public string $salaId = '';

    #[Url]
    public string $turno = '';

    /**
     * 'activos' (por defecto), 'baja' o 'todos'.
     */
    #[Url]
    public string $estado = 'activos';

    public function mount(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingSalaId(): void
    {
        $this->resetPage();
    }

    public function updatingTurno(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Nino>
     */
    #[Computed]
    public function ninos(): LengthAwarePaginator
    {
        return Nino::query()
            ->with('sala')
            ->when($this->estado === 'baja', fn (Builder $query) => $query->onlyTrashed())
            ->when($this->estado === 'todos', fn (Builder $query) => $query->withTrashed())
            ->when($this->busqueda !== '', function (Builder $query) {
                $termino = '%'.$this->busqueda.'%';

                $query->where(function (Builder $sub) use ($termino) {
                    $sub->where('nombres', 'like', $termino)
                        ->orWhere('apellidos', 'like', $termino)
                        ->orWhere('alias', 'like', $termino)
                        ->orWhere('dni', 'like', $termino);
                });
            })
            ->when($this->salaId !== '', fn (Builder $query) => $query->where('sala_id', $this->salaId))
            ->when($this->turno !== '', fn (Builder $query) => $query->whereHas(
                'sala',
                fn (Builder $sub) => $sub->where('turno', $this->turno),
            ))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15);
    }

    /**
     * @return Collection<int, Sala>
     */
    #[Computed]
    public function salas(): Collection
    {
        return Sala::query()->orderBy('nombre')->get();
    }

    /**
     * @return array<int, Turno>
     */
    #[Computed]
    public function turnos(): array
    {
        return Turno::cases();
    }

    /**
     * Abre el formulario de baja para un niño.
     */
    public function iniciarBaja(int $ninoId): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Eliminar));

        $this->ninoEnBajaId = $ninoId;
        $this->motivoBaja = '';
        $this->fechaBaja = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    /**
     * Confirma la baja lógica con motivo y fecha.
     */
    public function guardarBaja(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Eliminar));

        $datos = $this->validate($this->bajaRules());

        $nino = Nino::findOrFail($this->ninoEnBajaId);
        $nino->darDeBaja($datos['motivoBaja'], Carbon::parse($datos['fechaBaja']));

        $this->ninoEnBajaId = null;
        $this->js("\$flux.modal('formulario-baja').close()");

        Flux::toast(variant: 'success', text: __('Niño dado de baja.'));
    }

    /**
     * Restaura a un niño dado de baja.
     */
    public function restaurar(int $ninoId): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Eliminar));

        Nino::withTrashed()->findOrFail($ninoId)->restaurar();

        Flux::toast(variant: 'success', text: __('Niño restaurado.'));
    }

    public function render(): View
    {
        return view('livewire.ninos.index');
    }
}
