<?php

namespace App\Livewire\Ninos;

use App\Concerns\AlergiaValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Enums\SeveridadAlergia;
use App\Enums\TipoAlergia;
use App\Models\Alergia;
use App\Models\Nino;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Alergias y restricciones alimentarias de un niño: se muestran
 * destacadas acá y en un aviso en su legajo (ver Formulario) y en la
 * vista de la sala a la que asiste (ver App\Livewire\Salas\Ver).
 */
#[Title('Alergias')]
class Alergias extends Component
{
    use AlergiaValidationRules, AuthorizesRequests;

    public int $ninoId;

    public bool $soloLectura = false;

    public string $tipo = '';

    public string $severidad = '';

    public string $descripcion = '';

    public string $observaciones = '';

    public function mount(int $nino): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $modelo = Nino::findOrFail($nino);
        $modelo->registrarAcceso();

        $this->ninoId = $modelo->id;
        $this->soloLectura = ! Gate::allows(Modulo::Ninos->permiso(AccionPermiso::Editar));
    }

    #[Computed]
    public function nino(): Nino
    {
        return Nino::findOrFail($this->ninoId);
    }

    /**
     * @return Collection<int, Alergia>
     */
    #[Computed]
    public function alergias(): Collection
    {
        return $this->nino()->alergias()->latest()->get();
    }

    /**
     * @return array<int, TipoAlergia>
     */
    #[Computed]
    public function tipos(): array
    {
        return TipoAlergia::cases();
    }

    /**
     * @return array<int, SeveridadAlergia>
     */
    #[Computed]
    public function severidades(): array
    {
        return SeveridadAlergia::cases();
    }

    public function agregar(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Editar));

        $datos = $this->validate($this->alergiaRules());

        $this->nino()->alergias()->create([
            'tipo' => $datos['tipo'],
            'severidad' => $datos['severidad'],
            'descripcion' => $datos['descripcion'],
            'observaciones' => $datos['observaciones'] ?: null,
        ]);

        $this->reset(['tipo', 'severidad', 'descripcion', 'observaciones']);
        unset($this->alergias);

        Flux::toast(variant: 'success', text: __('Alergia registrada.'));
    }

    public function quitar(int $alergiaId): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Editar));

        $this->nino()->alergias()->whereKey($alergiaId)->delete();

        unset($this->alergias);

        Flux::toast(variant: 'success', text: __('Alergia eliminada.'));
    }

    public function render(): View
    {
        return view('livewire.ninos.alergias');
    }
}
