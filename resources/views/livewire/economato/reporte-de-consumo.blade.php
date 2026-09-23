<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Reporte de consumo') }}</flux:heading>
            <flux:subheading>{{ __('Consumo por artículo, comparado entre dos períodos.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" icon="archive-box" :href="route('economato.articulos.index')" wire:navigate>
                {{ __('Catálogo') }}
            </flux:button>

            <flux:button variant="primary" icon="arrow-down-tray" wire:click="exportarCsv">
                {{ __('Exportar a CSV') }}
            </flux:button>
        </div>
    </div>

    <div class="flex flex-wrap gap-6">
        <div class="flex items-end gap-2">
            <flux:input wire:model.live="desde1" :label="__('Período 1: desde')" type="date" />
            <flux:input wire:model.live="hasta1" :label="__('hasta')" type="date" />
        </div>

        <div class="flex items-end gap-2">
            <flux:input wire:model.live="desde2" :label="__('Período 2: desde')" type="date" />
            <flux:input wire:model.live="hasta2" :label="__('hasta')" type="date" />
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Artículo') }}</flux:table.column>
            <flux:table.column>{{ __('Consumo período 1') }}</flux:table.column>
            <flux:table.column>{{ __('Consumo período 2') }}</flux:table.column>
            <flux:table.column>{{ __('Diferencia') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->reporte as $fila)
                <flux:table.row wire:key="articulo-{{ $fila['articulo']->id }}">
                    <flux:table.cell>{{ $fila['articulo']->nombre }}</flux:table.cell>
                    <flux:table.cell>{{ $fila['consumo1'] }} {{ $fila['articulo']->unidad_medida }}</flux:table.cell>
                    <flux:table.cell>{{ $fila['consumo2'] }} {{ $fila['articulo']->unidad_medida }}</flux:table.cell>
                    <flux:table.cell>{{ $fila['diferencia'] }} {{ $fila['articulo']->unidad_medida }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <flux:text>{{ __('Todavía no hay artículos en el catálogo.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
