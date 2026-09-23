<?php

namespace App\Services;

use App\Models\Articulo;
use App\Models\Lote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Alertas de economato: lotes próximos a vencer y artículos bajo su stock
 * mínimo. Institución-scoped a través de los global scopes de Lote y
 * Articulo (PerteneceAInstitucion), así que siempre miran la institución
 * activa.
 */
class AlertasDeEconomato
{
    /**
     * Lotes con stock que vencen dentro de los próximos $diasAviso días,
     * del que vence antes al que vence después.
     *
     * @return Collection<int, array{lote: Lote, stock: float}>
     */
    public function lotesPorVencer(int $diasAviso): Collection
    {
        $limite = Carbon::today()->addDays($diasAviso);

        return Lote::query()
            ->where('fecha_vencimiento', '<=', $limite)
            ->with(['articulo', 'movimientos'])
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn (Lote $lote): array => ['lote' => $lote, 'stock' => $lote->stockActual()])
            ->filter(fn (array $fila): bool => $fila['stock'] > 0)
            ->values();
    }

    /**
     * Artículos cuyo stock total está por debajo de su stock mínimo
     * configurado.
     *
     * @return Collection<int, array{articulo: Articulo, stock: float}>
     */
    public function articulosBajoMinimo(): Collection
    {
        return Articulo::query()
            ->with('lotes.movimientos')
            ->get()
            ->map(fn (Articulo $articulo): array => [
                'articulo' => $articulo,
                'stock' => $articulo->lotes->sum(fn (Lote $lote): float => $lote->stockActual()),
            ])
            ->filter(fn (array $fila): bool => $fila['stock'] < (float) $fila['articulo']->stock_minimo)
            ->values();
    }
}
