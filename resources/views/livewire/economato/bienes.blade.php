<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Inventario patrimonial') }}</flux:heading>
                <flux:subheading>{{ __('Bienes muebles y materiales de la institución.') }}</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" icon="map-pin" :href="route('economato.inventario.index')" wire:navigate>
                    {{ __('Reporte por ubicación') }}
                </flux:button>

                @can('economato.crear')
                    <flux:modal.trigger name="formulario-bien">
                        <flux:button variant="primary" icon="plus" wire:click="nuevo">
                            {{ __('Nuevo bien') }}
                        </flux:button>
                    </flux:modal.trigger>
                @endcan
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Código') }}</flux:table.column>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Ubicación') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->bienes as $bien)
                    <flux:table.row wire:key="bien-{{ $bien->id }}">
                        <flux:table.cell>{{ $bien->codigo }}</flux:table.cell>
                        <flux:table.cell>{{ $bien->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $bien->ubicacion }}</flux:table.cell>
                        <flux:table.cell class="capitalize">{{ $bien->estado_conservacion->value }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                @can('economato.editar')
                                    <flux:modal.trigger name="formulario-bien">
                                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editar({{ $bien->id }})">
                                            {{ __('Editar') }}
                                        </flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal.trigger name="formulario-movimiento-ubicacion">
                                        <flux:button size="sm" variant="ghost" icon="map-pin" wire:click="nuevoMovimiento({{ $bien->id }})">
                                            {{ __('Mover') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text>{{ __('Todavía no hay bienes cargados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->bienes->links() }}
    </div>

    <flux:modal name="formulario-bien" :show="$errors->has('nombre') || $errors->has('codigo') || $errors->has('ubicacion') || $errors->has('estadoConservacion')" class="md:w-96">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $bienId ? __('Editar bien') : __('Nuevo bien') }}
            </flux:heading>

            <flux:input wire:model="nombre" :label="__('Nombre')" />
            <flux:input wire:model="codigo" :label="__('Código')" placeholder="INV-0001" />

            @unless ($bienId)
                <flux:input wire:model="ubicacion" :label="__('Ubicación')" placeholder="Depósito" />
            @endunless

            <flux:select wire:model="estadoConservacion" :label="__('Estado de conservación')">
                <flux:select.option value="">{{ __('Seleccionar estado') }}</flux:select.option>
                @foreach ($this->estados as $opcion)
                    <flux:select.option value="{{ $opcion->value }}" class="capitalize">
                        {{ $opcion->value }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="formulario-movimiento-ubicacion" :show="$errors->has('ubicacionNueva') || $errors->has('fechaMovimiento')" class="md:w-96">
        <form wire:submit="moverUbicacion" class="space-y-6">
            <flux:heading size="lg">{{ __('Mover ubicación') }}</flux:heading>

            <flux:input wire:model="ubicacionNueva" :label="__('Ubicación nueva')" />
            <flux:input wire:model="fechaMovimiento" :label="__('Fecha')" type="date" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
