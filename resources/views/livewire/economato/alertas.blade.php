<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Alertas de economato') }}</flux:heading>
                <flux:subheading>{{ __('Lotes próximos a vencer y artículos bajo su stock mínimo.') }}</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" icon="cube" :href="route('economato.stock.index')" wire:navigate>
                    {{ __('Stock') }}
                </flux:button>

                <flux:button variant="ghost" icon="book-open" :href="route('economato.movimientos.index')" wire:navigate>
                    {{ __('Movimientos') }}
                </flux:button>
            </div>
        </div>

        @can('economato.editar')
            <form wire:submit="guardarConfiguracion" class="flex items-end gap-4">
                <flux:input
                    wire:model="diasAvisoVencimiento"
                    :label="__('Avisar con cuántos días de anticipación')"
                    type="number"
                    min="1"
                    max="365"
                    class="max-w-48"
                />

                <flux:button type="submit" variant="filled">{{ __('Guardar') }}</flux:button>
            </form>
        @endcan

        <div>
            <flux:heading size="lg">{{ __('Lotes próximos a vencer') }}</flux:heading>

            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>{{ __('Artículo') }}</flux:table.column>
                    <flux:table.column>{{ __('Vencimiento') }}</flux:table.column>
                    <flux:table.column>{{ __('Stock del lote') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->lotesPorVencer as $fila)
                        <flux:table.row wire:key="lote-{{ $fila['lote']->id }}">
                            <flux:table.cell>{{ $fila['lote']->articulo->nombre }}</flux:table.cell>
                            <flux:table.cell>{{ $fila['lote']->fecha_vencimiento->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $fila['stock'] }} {{ $fila['lote']->articulo->unidad_medida }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3">
                                <flux:text>{{ __('No hay lotes por vencer.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div>
            <flux:heading size="lg">{{ __('Artículos bajo el stock mínimo') }}</flux:heading>

            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>{{ __('Artículo') }}</flux:table.column>
                    <flux:table.column>{{ __('Stock actual') }}</flux:table.column>
                    <flux:table.column>{{ __('Stock mínimo') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->articulosBajoMinimo as $fila)
                        <flux:table.row wire:key="articulo-{{ $fila['articulo']->id }}">
                            <flux:table.cell>{{ $fila['articulo']->nombre }}</flux:table.cell>
                            <flux:table.cell>{{ $fila['stock'] }} {{ $fila['articulo']->unidad_medida }}</flux:table.cell>
                            <flux:table.cell>{{ $fila['articulo']->stock_minimo }} {{ $fila['articulo']->unidad_medida }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3">
                                <flux:text>{{ __('Ningún artículo está bajo su stock mínimo.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
