<div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Alertas de economato') }}</flux:heading>

        <flux:button size="sm" variant="ghost" :href="route('economato.alertas.index')" wire:navigate>
            {{ __('Ver todas') }}
        </flux:button>
    </div>

    @if ($this->lotesPorVencer->isEmpty() && $this->articulosBajoMinimo->isEmpty())
        <flux:text class="mt-2" variant="subtle">{{ __('Sin alertas por ahora.') }}</flux:text>
    @else
        <div class="mt-3 flex flex-col gap-1">
            @foreach ($this->lotesPorVencer->take(5) as $fila)
                <flux:text size="sm">
                    {{ __(':articulo vence el :fecha', [
                        'articulo' => $fila['lote']->articulo->nombre,
                        'fecha' => $fila['lote']->fecha_vencimiento->format('d/m/Y'),
                    ]) }}
                </flux:text>
            @endforeach

            @foreach ($this->articulosBajoMinimo->take(5) as $fila)
                <flux:text size="sm" class="text-red-600 dark:text-red-400">
                    {{ __(':articulo por debajo del stock mínimo (:stock :unidad)', [
                        'articulo' => $fila['articulo']->nombre,
                        'stock' => $fila['stock'],
                        'unidad' => $fila['articulo']->unidad_medida,
                    ]) }}
                </flux:text>
            @endforeach
        </div>
    @endif
</div>
