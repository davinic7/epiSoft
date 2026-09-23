<?php

namespace App\Livewire\Economato;

use App\Concerns\MovimientoDeStockValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Enums\TipoMovimientoStock;
use App\Models\Articulo;
use App\Models\Lote;
use App\Models\MovimientoStock;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Libro de movimientos de entrada y salida de economato (guía, cap. 3.4,
 * pág. 23): el registro cronológico de todo lo que entra y sale del
 * economato, con quién lo entrega o recibe. Los ingresos se registran
 * desde la vista de stock (crean un lote nuevo); esta pantalla registra
 * las salidas y es el libro completo de ambos tipos de movimiento.
 *
 * Un movimiento no se edita ni se borra: se anula con un contramovimiento
 * de signo contrario (ver anular()).
 */
#[Title('Libro de movimientos')]
class Movimientos extends Component
{
    use AuthorizesRequests, MovimientoDeStockValidationRules, WithPagination;

    public ?int $articuloId = null;

    public string $cantidad = '';

    public string $fecha = '';

    public string $origen = '';

    public string $contraparte = '';

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));
    }

    /**
     * @return LengthAwarePaginator<int, MovimientoStock>
     */
    #[Computed]
    public function movimientos(): LengthAwarePaginator
    {
        return MovimientoStock::query()
            ->with(['lote.articulo', 'anulaA', 'contramovimiento'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);
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
     * Abre el formulario de salida para un artículo.
     */
    public function nuevaSalida(?int $articuloId = null): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $this->articuloId = $articuloId;
        $this->cantidad = '';
        $this->fecha = now()->format('Y-m-d');
        $this->origen = '';
        $this->contraparte = '';
        $this->resetErrorBag();
    }

    /**
     * Registra la salida: reparte la cantidad entre los lotes vigentes del
     * artículo, empezando por el que vence antes, y crea un movimiento de
     * salida por cada lote consumido.
     */
    public function registrarSalida(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $datos = $this->validate($this->salidaRules());

        $articulo = Articulo::findOrFail((int) $datos['articuloId']);

        $lotesConStock = $articulo->lotes()->with('movimientos')->orderBy('fecha_vencimiento')->get()
            ->map(fn (Lote $lote): array => ['lote' => $lote, 'stock' => $lote->stockActual()])
            ->filter(fn (array $fila): bool => $fila['stock'] > 0);

        $cantidadSolicitada = (float) $datos['cantidad'];

        if ($cantidadSolicitada > $lotesConStock->sum('stock')) {
            $this->addError('cantidad', __('No hay stock suficiente: quedan :stock :unidad.', [
                'stock' => $lotesConStock->sum('stock'),
                'unidad' => $articulo->unidad_medida,
            ]));

            return;
        }

        $restante = $cantidadSolicitada;

        foreach ($lotesConStock as $fila) {
            if ($restante <= 0) {
                break;
            }

            $consumir = min($restante, $fila['stock']);

            $fila['lote']->movimientos()->create([
                'tipo' => TipoMovimientoStock::Salida,
                'cantidad' => $consumir,
                'fecha' => $datos['fecha'],
                'origen' => $datos['origen'],
                'contraparte' => $datos['contraparte'],
            ]);

            $restante -= $consumir;
        }

        $this->js("\$flux.modal('formulario-salida').close()");

        Flux::toast(variant: 'success', text: __('Salida registrada.'));
    }

    /**
     * Anula un movimiento con un contramovimiento de signo contrario. No
     * modifica ni borra el movimiento original.
     */
    public function anular(int $movimientoId): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        $movimiento = MovimientoStock::with('lote.movimientos')->findOrFail($movimientoId);

        if ($movimiento->anula_a_id !== null) {
            Flux::toast(variant: 'danger', text: __('Un contramovimiento no se puede anular.'));

            return;
        }

        if ($movimiento->contramovimiento()->exists()) {
            Flux::toast(variant: 'danger', text: __('Ese movimiento ya fue anulado.'));

            return;
        }

        if ($movimiento->tipo === TipoMovimientoStock::Entrada && $movimiento->lote->stockActual() < (float) $movimiento->cantidad) {
            Flux::toast(variant: 'danger', text: __('No se puede anular: parte de este ingreso ya se usó en una salida.'));

            return;
        }

        $movimiento->lote->movimientos()->create([
            'tipo' => $movimiento->tipo === TipoMovimientoStock::Entrada ? TipoMovimientoStock::Salida : TipoMovimientoStock::Entrada,
            'cantidad' => $movimiento->cantidad,
            'fecha' => Carbon::today(),
            'origen' => __('Anulación del movimiento #:id', ['id' => $movimiento->id]),
            'anula_a_id' => $movimiento->id,
        ]);

        Flux::toast(variant: 'success', text: __('Movimiento anulado.'));
    }

    public function render(): View
    {
        return view('livewire.economato.movimientos');
    }
}
