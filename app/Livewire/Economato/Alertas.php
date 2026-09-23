<?php

namespace App\Livewire\Economato;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Articulo;
use App\Models\Institucion;
use App\Models\Lote;
use App\Services\AlertasDeEconomato;
use App\Support\InstitucionContext;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Avisos de vencimiento próximo y de stock bajo el mínimo. Un resumen
 * acotado de esta misma información aparece también en el dashboard (ver
 * App\Livewire\Economato\AlertasDashboard).
 */
#[Title('Alertas de economato')]
class Alertas extends Component
{
    use AuthorizesRequests;

    public string $diasAvisoVencimiento = '';

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));

        $this->diasAvisoVencimiento = (string) $this->institucion()->dias_aviso_vencimiento;
    }

    /**
     * @return Collection<int, array{lote: Lote, stock: float}>
     */
    #[Computed]
    public function lotesPorVencer(): Collection
    {
        return app(AlertasDeEconomato::class)->lotesPorVencer($this->institucion()->dias_aviso_vencimiento);
    }

    /**
     * @return Collection<int, array{articulo: Articulo, stock: float}>
     */
    #[Computed]
    public function articulosBajoMinimo(): Collection
    {
        return app(AlertasDeEconomato::class)->articulosBajoMinimo();
    }

    /**
     * Actualiza los días de anticipación con que se avisa un vencimiento.
     */
    public function guardarConfiguracion(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        $datos = $this->validate([
            'diasAvisoVencimiento' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->institucion()->update(['dias_aviso_vencimiento' => $datos['diasAvisoVencimiento']]);

        Flux::toast(variant: 'success', text: __('Configuración guardada.'));
    }

    private function institucion(): Institucion
    {
        return Institucion::findOrFail(app(InstitucionContext::class)->id());
    }

    public function render(): View
    {
        return view('livewire.economato.alertas');
    }
}
