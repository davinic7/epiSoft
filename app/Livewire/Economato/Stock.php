<?php

namespace App\Livewire\Economato;

use App\Concerns\IngresoDeLoteValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Enums\TipoMovimientoStock;
use App\Models\Articulo;
use App\Models\Lote;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Vista de stock por artículo, con el desglose de sus lotes vigentes (los
 * que todavía tienen stock, ordenados por el que vence antes), y el alta
 * de un ingreso de stock: cada ingreso crea un lote nuevo con su propia
 * fecha de vencimiento, aunque sea del mismo artículo que uno existente.
 */
#[Title('Stock de economato')]
class Stock extends Component
{
    use AuthorizesRequests, IngresoDeLoteValidationRules, WithPagination;

    public ?int $articuloId = null;

    public string $fechaVencimiento = '';

    public string $cantidad = '';

    public string $fecha = '';

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));
    }

    /**
     * @return LengthAwarePaginator<int, Articulo>
     */
    #[Computed]
    public function articulos(): LengthAwarePaginator
    {
        return Articulo::query()
            ->with('lotes.movimientos')
            ->orderBy('nombre')
            ->paginate(10);
    }

    /**
     * @return Collection<int, Articulo>
     */
    #[Computed]
    public function articulosParaSelect(): Collection
    {
        return Articulo::query()->orderBy('nombre')->get();
    }

    /**
     * Lotes de un artículo con stock actual mayor a cero, del más próximo a
     * vencer al más lejano.
     *
     * @return array<int, array{lote: Lote, stock: float}>
     */
    public function lotesConStock(Articulo $articulo): array
    {
        return $articulo->lotes
            ->sortBy(fn (Lote $lote): string => $lote->fecha_vencimiento->format('Y-m-d'))
            ->map(fn (Lote $lote): array => ['lote' => $lote, 'stock' => $lote->stockActual()])
            ->filter(fn (array $fila): bool => $fila['stock'] > 0)
            ->values()
            ->all();
    }

    /**
     * Abre el formulario de ingreso para un artículo.
     */
    public function nuevoIngreso(?int $articuloId = null): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $this->articuloId = $articuloId;
        $this->fechaVencimiento = '';
        $this->cantidad = '';
        $this->fecha = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    /**
     * Registra el ingreso: crea el lote y su movimiento de entrada.
     */
    public function registrarIngreso(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $datos = $this->validate($this->ingresoRules());

        $lote = Lote::create([
            'articulo_id' => $datos['articuloId'],
            'fecha_vencimiento' => $datos['fechaVencimiento'],
        ]);

        $lote->movimientos()->create([
            'tipo' => TipoMovimientoStock::Entrada,
            'cantidad' => $datos['cantidad'],
            'fecha' => $datos['fecha'],
        ]);

        $this->js("\$flux.modal('formulario-ingreso').close()");

        Flux::toast(variant: 'success', text: __('Ingreso registrado.'));
    }

    public function render(): View
    {
        return view('livewire.economato.stock');
    }
}
