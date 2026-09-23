<?php

namespace App\Livewire\Economato;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Enums\RolInstitucional;
use App\Enums\TipoMovimientoStock;
use App\Models\Articulo;
use App\Models\CierreMensual;
use App\Models\MovimientoStock;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cierre mensual de economato (guía, cap. 3.4): cerrar un período bloquea
 * nuevos movimientos de stock con fecha en ese mes (ver
 * CierreMensual::estaCerradoParaFecha(), consultado desde
 * Economato\Stock::registrarIngreso() y Economato\Movimientos). Reabrir
 * un período cerrado es una acción reservada al equipo de coordinación,
 * no al permiso general de editar economato (que también tiene el
 * encargado de economato): el mismo patrón que la aprobación del menú
 * semanal.
 */
#[Title('Cierre mensual de economato')]
class CierresMensuales extends Component
{
    use AuthorizesRequests;

    public string $periodoACerrar = '';

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));

        $this->periodoACerrar = Carbon::now()->format('Y-m');
    }

    /**
     * @return LengthAwarePaginator<int, CierreMensual>
     */
    #[Computed]
    public function cierres(): LengthAwarePaginator
    {
        return CierreMensual::query()->orderByDesc('cerrado_en')->paginate(10);
    }

    /**
     * Saldo inicial y final de cada artículo para el período elegido, más
     * si ese período ya está cerrado.
     *
     * @return array<int, array{articulo: Articulo, saldoInicial: float, saldoFinal: float}>
     */
    #[Computed]
    public function reporteDeCierre(): array
    {
        $inicioDeMes = Carbon::parse($this->periodoACerrar.'-01')->startOfMonth();
        $finDeMes = $inicioDeMes->copy()->endOfMonth();

        return Articulo::query()->orderBy('nombre')->get()
            ->map(fn (Articulo $articulo): array => [
                'articulo' => $articulo,
                'saldoInicial' => $this->saldoALaFecha($articulo, $inicioDeMes->copy()->subDay()),
                'saldoFinal' => $this->saldoALaFecha($articulo, $finDeMes),
            ])
            ->all();
    }

    #[Computed]
    public function periodoYaCerrado(): bool
    {
        return CierreMensual::estaCerradoParaFecha(Carbon::parse($this->periodoACerrar.'-01'));
    }

    private function saldoALaFecha(Articulo $articulo, Carbon $fecha): float
    {
        return MovimientoStock::query()
            ->whereHas('lote', fn (Builder $query) => $query->where('articulo_id', $articulo->id))
            ->where('fecha', '<=', $fecha)
            ->get()
            ->sum(fn (MovimientoStock $movimiento): float => $movimiento->tipo === TipoMovimientoStock::Entrada
                ? (float) $movimiento->cantidad
                : -(float) $movimiento->cantidad);
    }

    public function cerrarPeriodo(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        if ($this->periodoYaCerrado()) {
            Flux::toast(variant: 'danger', text: __('Ese período ya está cerrado.'));

            return;
        }

        CierreMensual::create([
            'periodo' => Carbon::parse($this->periodoACerrar.'-01')->startOfMonth(),
            'cerrado_por_id' => auth()->id(),
            'cerrado_en' => Carbon::now(),
        ]);

        unset($this->periodoYaCerrado);

        Flux::toast(variant: 'success', text: __('Período cerrado.'));
    }

    public function reabrir(int $cierreId): void
    {
        abort_unless(auth()->user()->hasRole(RolInstitucional::EquipoCoordinacion->value), 403);

        $cierre = CierreMensual::findOrFail($cierreId);

        if ($cierre->reabierto_en !== null) {
            Flux::toast(variant: 'danger', text: __('Ese cierre ya estaba reabierto.'));

            return;
        }

        $cierre->reabrir(auth()->user());

        unset($this->periodoYaCerrado);

        Flux::toast(variant: 'success', text: __('Período reabierto.'));
    }

    public function render(): View
    {
        return view('livewire.economato.cierres-mensuales');
    }
}
