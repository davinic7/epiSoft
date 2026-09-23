<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Stock de economato') }}</flux:heading>
                <flux:subheading>{{ __('Stock actual por artículo, desglosado por lote y fecha de vencimiento.') }}</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" icon="archive-box" :href="route('economato.articulos.index')" wire:navigate>
                    {{ __('Catálogo') }}
                </flux:button>

                <flux:button variant="ghost" icon="book-open" :href="route('economato.movimientos.index')" wire:navigate>
                    {{ __('Movimientos') }}
                </flux:button>

                <flux:button variant="ghost" icon="bell-alert" :href="route('economato.alertas.index')" wire:navigate>
                    {{ __('Alertas') }}
                </flux:button>

                <flux:button variant="ghost" icon="calendar-days" :href="route('economato.menus.index')" wire:navigate>
                    {{ __('Menús') }}
                </flux:button>

                @can('economato.crear')
                    <flux:modal.trigger name="formulario-ingreso">
                        <flux:button variant="primary" icon="plus" wire:click="nuevoIngreso">
                            {{ __('Nuevo ingreso') }}
                        </flux:button>
                    </flux:modal.trigger>
                @endcan
            </div>
        </div>

        <div class="flex flex-col gap-4">
            @forelse ($this->articulos as $articulo)
                @php $lotes = $this->lotesConStock($articulo); @endphp

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="articulo-{{ $articulo->id }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">{{ $articulo->nombre }}</flux:heading>
                            <flux:text size="sm" variant="subtle" class="capitalize">{{ $articulo->categoria->value }}</flux:text>
                        </div>

                        <flux:badge :color="array_sum(array_column($lotes, 'stock')) < (float) $articulo->stock_minimo ? 'red' : 'green'">
                            {{ __('Stock: :stock :unidad', ['stock' => array_sum(array_column($lotes, 'stock')), 'unidad' => $articulo->unidad_medida]) }}
                        </flux:badge>
                    </div>

                    @if (count($lotes) > 0)
                        <flux:table class="mt-4">
                            <flux:table.columns>
                                <flux:table.column>{{ __('Vencimiento') }}</flux:table.column>
                                <flux:table.column>{{ __('Stock del lote') }}</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($lotes as $fila)
                                    <flux:table.row wire:key="lote-{{ $fila['lote']->id }}">
                                        <flux:table.cell>{{ $fila['lote']->fecha_vencimiento->format('d/m/Y') }}</flux:table.cell>
                                        <flux:table.cell>{{ $fila['stock'] }} {{ $articulo->unidad_medida }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <flux:text size="sm" variant="subtle" class="mt-4">{{ __('Sin lotes con stock.') }}</flux:text>
                    @endif
                </div>
            @empty
                <flux:text>{{ __('Todavía no hay artículos en el catálogo.') }}</flux:text>
            @endforelse
        </div>

        {{ $this->articulos->links() }}
    </div>

    <flux:modal name="formulario-ingreso" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="registrarIngreso" class="space-y-6">
            <flux:heading size="lg">{{ __('Nuevo ingreso') }}</flux:heading>

            <flux:select wire:model="articuloId" :label="__('Artículo')">
                <flux:select.option value="">{{ __('Seleccionar artículo') }}</flux:select.option>
                @foreach ($this->articulosParaSelect as $opcion)
                    <flux:select.option value="{{ $opcion->id }}">{{ $opcion->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="cantidad" :label="__('Cantidad')" type="number" step="0.01" min="0.01" />

            <flux:input wire:model="fechaVencimiento" :label="__('Fecha de vencimiento')" type="date" />

            <flux:input wire:model="fecha" :label="__('Fecha de ingreso')" type="date" />

            <flux:input wire:model="origen" :label="__('Origen')" placeholder="Dirección Provincial de Primera Infancia" />

            <flux:input wire:model="contraparte" :label="__('Quién entrega')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
