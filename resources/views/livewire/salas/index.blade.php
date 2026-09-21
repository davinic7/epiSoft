<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Salas') }}</flux:heading>
                <flux:subheading>{{ __('Salas, turnos y cupos de la institución.') }}</flux:subheading>
            </div>

            @can('ninos.crear')
                <flux:modal.trigger name="formulario-sala">
                    <flux:button variant="primary" icon="plus" wire:click="nuevo">
                        {{ __('Nueva sala') }}
                    </flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Turno') }}</flux:table.column>
                <flux:table.column>{{ __('Capacidad') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->salas as $sala)
                    <flux:table.row wire:key="sala-{{ $sala->id }}">
                        <flux:table.cell>{{ $sala->nombre }}</flux:table.cell>
                        <flux:table.cell class="capitalize">{{ $sala->turno->value }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->capacidad }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                @can('ninos.editar')
                                    <flux:modal.trigger name="formulario-sala">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                            wire:click="editar({{ $sala->id }})"
                                        >
                                            {{ __('Editar') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endcan

                                @can('ninos.eliminar')
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="eliminar({{ $sala->id }})"
                                        wire:confirm="{{ __('¿Eliminar :nombre? Esta acción no se puede deshacer.', ['nombre' => $sala->nombre]) }}"
                                    >
                                        {{ __('Eliminar') }}
                                    </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <flux:text>{{ __('Todavía no hay salas cargadas.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->salas->links() }}
    </div>

    <flux:modal name="formulario-sala" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $salaId ? __('Editar sala') : __('Nueva sala') }}
            </flux:heading>

            <flux:input wire:model="nombre" :label="__('Nombre')" placeholder="Sala de bebés" />

            <flux:select wire:model="turno" :label="__('Turno')">
                <flux:select.option value="">{{ __('Seleccionar turno') }}</flux:select.option>
                @foreach ($this->turnos as $opcion)
                    <flux:select.option value="{{ $opcion->value }}" class="capitalize">
                        {{ $opcion->value }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="capacidad" :label="__('Capacidad máxima')" type="number" min="1" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
