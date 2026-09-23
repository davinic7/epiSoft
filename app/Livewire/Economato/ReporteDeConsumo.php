<?php

namespace App\Livewire\Economato;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Enums\TipoMovimientoStock;
use App\Models\Articulo;
use App\Models\MovimientoStock;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consumo de economato (salidas de stock) por artículo, comparado entre
 * dos períodos, exportable a CSV (no .xlsx: ver el mismo criterio ya
 * documentado en Importar.php y docs/BACKLOG.md, "Importación inicial de
 * datos existentes").
 */
#[Title('Reporte de consumo')]
class ReporteDeConsumo extends Component
{
    use AuthorizesRequests;

    public string $desde1;

    public string $hasta1;

    public string $desde2;

    public string $hasta2;

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));

        $this->desde1 = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta1 = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->desde2 = Carbon::now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $this->hasta2 = Carbon::now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d');
    }

    /**
     * Consumo neto (salidas menos las entradas que anulan una salida, para
     * no contar como consumo una salida que se corrigió) de cada artículo
     * en los dos períodos elegidos.
     *
     * @return array<int, array{articulo: Articulo, consumo1: float, consumo2: float, diferencia: float}>
     */
    #[Computed]
    public function reporte(): array
    {
        $articulos = Articulo::query()->orderBy('nombre')->get();

        return $articulos->map(function (Articulo $articulo): array {
            $consumo1 = $this->consumoNeto($articulo, $this->desde1, $this->hasta1);
            $consumo2 = $this->consumoNeto($articulo, $this->desde2, $this->hasta2);

            return [
                'articulo' => $articulo,
                'consumo1' => $consumo1,
                'consumo2' => $consumo2,
                'diferencia' => $consumo1 - $consumo2,
            ];
        })->all();
    }

    private function consumoNeto(Articulo $articulo, string $desde, string $hasta): float
    {
        /** @var Collection<int, MovimientoStock> $movimientos */
        $movimientos = MovimientoStock::query()
            ->whereHas('lote', fn (Builder $query) => $query->where('articulo_id', $articulo->id))
            ->whereBetween('fecha', [$desde, $hasta])
            ->get();

        $salidas = $movimientos
            ->where('tipo', TipoMovimientoStock::Salida)
            ->sum(fn (MovimientoStock $movimiento): float => (float) $movimiento->cantidad);

        $anulacionesDeSalida = $movimientos
            ->where('tipo', TipoMovimientoStock::Entrada)
            ->whereNotNull('anula_a_id')
            ->sum(fn (MovimientoStock $movimiento): float => (float) $movimiento->cantidad);

        return $salidas - $anulacionesDeSalida;
    }

    public function exportarCsv(): StreamedResponse
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));

        $filas = $this->reporte();

        return Response::streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');

            if ($salida === false) {
                return;
            }

            fputcsv($salida, ['artículo', 'consumo período 1', 'consumo período 2', 'diferencia']);

            foreach ($filas as $fila) {
                fputcsv($salida, [
                    $fila['articulo']->nombre,
                    $fila['consumo1'],
                    $fila['consumo2'],
                    $fila['diferencia'],
                ]);
            }

            fclose($salida);
        }, 'reporte-de-consumo.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        return view('livewire.economato.reporte-de-consumo');
    }
}
