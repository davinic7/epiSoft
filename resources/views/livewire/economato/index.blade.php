<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Economato') }}</flux:heading>
                <flux:subheading>{{ __('Catálogo de artículos e insumos de la institución.') }}</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" icon="cube" :href="route('economato.stock.index')" wire:navigate>
                    {{ __('Stock') }}
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

                <flux:button variant="ghost" icon="map-pin" :href="route('economato.bienes.index')" wire:navigate>
                    {{ __('Inventario') }}
                </flux:button>

                <flux:button variant="ghost" icon="chart-bar" :href="route('economato.reporte-de-consumo.index')" wire:navigate>
                    {{ __('Consumo') }}
                </flux:button>

                @can('economato.crear')
                    <flux:modal.trigger name="formulario-articulo">
                        <flux:button variant="primary" icon="plus" wire:click="nuevo">
                            {{ __('Nuevo artículo') }}
                        </flux:button>
                    </flux:modal.trigger>
                @endcan
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <flux:input
                wire:model.live.debounce.400ms="busqueda"
                :label="__('Buscar')"
                placeholder="Nombre del artículo"
                icon="magnifying-glass"
                class="max-w-64"
            />

            <flux:select wire:model.live="filtroCategoria" :label="__('Categoría')" class="max-w-48">
                <flux:select.option value="">{{ __('Todas') }}</flux:select.option>
                @foreach ($this->categorias as $opcion)
                    <flux:select.option value="{{ $opcion->value }}" class="capitalize">
                        {{ $opcion->value }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Categoría') }}</flux:table.column>
                <flux:table.column>{{ __('Unidad de medida') }}</flux:table.column>
                <flux:table.column>{{ __('Stock mínimo') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->articulos as $articulo)
                    <flux:table.row wire:key="articulo-{{ $articulo->id }}">
                        <flux:table.cell>{{ $articulo->nombre }}</flux:table.cell>
                        <flux:table.cell class="capitalize">{{ $articulo->categoria->value }}</flux:table.cell>
                        <flux:table.cell>{{ $articulo->unidad_medida }}</flux:table.cell>
                        <flux:table.cell>{{ $articulo->stock_minimo }}</flux:table.cell>
                        <flux:table.cell>
                            @can('economato.editar')
                                <flux:modal.trigger name="formulario-articulo">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                        wire:click="editar({{ $articulo->id }})"
                                    >
                                        {{ __('Editar') }}
                                    </flux:button>
                                </flux:modal.trigger>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text>{{ __('Todavía no hay artículos cargados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->articulos->links() }}
    </div>

    <flux:modal name="formulario-articulo" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $articuloId ? __('Editar artículo') : __('Nuevo artículo') }}
            </flux:heading>

            <flux:input wire:model="nombre" :label="__('Nombre')" placeholder="Arroz" />

            <flux:select wire:model="categoria" :label="__('Categoría')">
                <flux:select.option value="">{{ __('Seleccionar categoría') }}</flux:select.option>
                @foreach ($this->categorias as $opcion)
                    <flux:select.option value="{{ $opcion->value }}" class="capitalize">
                        {{ $opcion->value }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="unidadMedida" :label="__('Unidad de medida')" placeholder="kg" />

            <flux:input wire:model="stockMinimo" :label="__('Stock mínimo')" type="number" step="0.01" min="0" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
