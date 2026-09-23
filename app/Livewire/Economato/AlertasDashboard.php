<?php

namespace App\Livewire\Economato;

use App\Models\Articulo;
use App\Models\Institucion;
use App\Models\Lote;
use App\Services\AlertasDeEconomato;
use App\Support\InstitucionContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Resumen de alertas de economato para el dashboard: cuenta lotes por
 * vencer y artículos bajo su stock mínimo. Solo se embebe en el dashboard
 * cuando el usuario tiene economato.ver (ver resources/views/dashboard.blade.php).
 */
class AlertasDashboard extends Component
{
    /**
     * @return Collection<int, array{lote: Lote, stock: float}>
     */
    #[Computed]
    public function lotesPorVencer(): Collection
    {
        $institucion = Institucion::findOrFail(app(InstitucionContext::class)->id());

        return app(AlertasDeEconomato::class)->lotesPorVencer($institucion->dias_aviso_vencimiento);
    }

    /**
     * @return Collection<int, array{articulo: Articulo, stock: float}>
     */
    #[Computed]
    public function articulosBajoMinimo(): Collection
    {
        return app(AlertasDeEconomato::class)->articulosBajoMinimo();
    }

    public function render(): View
    {
        return view('livewire.economato.alertas-dashboard');
    }
}
