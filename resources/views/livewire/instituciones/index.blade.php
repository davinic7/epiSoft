<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Instituciones') }}</flux:heading>
                <flux:subheading>{{ __('Alta y edición de los espacios de primera infancia.') }}</flux:subheading>
            </div>

            <flux:modal.trigger name="formulario-institucion">
                <flux:button variant="primary" icon="plus" wire:click="nuevo">
                    {{ __('Nueva institución') }}
                </flux:button>
            </flux:modal.trigger>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Dirección') }}</flux:table.column>
                <flux:table.column>{{ __('CUIT') }}</flux:table.column>
                <flux:table.column>{{ __('Referente') }}</flux:table.column>
                <flux:table.column>{{ __('Capacidad') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->instituciones as $institucion)
                    <flux:table.row wire:key="institucion-{{ $institucion->id }}">
                        <flux:table.cell>{{ $institucion->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $institucion->direccion }}</flux:table.cell>
                        <flux:table.cell>{{ $institucion->cuit }}</flux:table.cell>
                        <flux:table.cell>{{ $institucion->referente }}</flux:table.cell>
                        <flux:table.cell>{{ $institucion->capacidad }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:modal.trigger name="formulario-institucion">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                        wire:click="editar({{ $institucion->id }})"
                                    >
                                        {{ __('Editar') }}
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="eliminar({{ $institucion->id }})"
                                    wire:confirm="{{ __('¿Eliminar :nombre? Esta acción no se puede deshacer.', ['nombre' => $institucion->nombre]) }}"
                                >
                                    {{ __('Eliminar') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text>{{ __('Todavía no hay instituciones cargadas.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->instituciones->links() }}
    </div>

    <flux:modal name="formulario-institucion" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $institucionId ? __('Editar institución') : __('Nueva institución') }}
            </flux:heading>

            <flux:input wire:model="nombre" :label="__('Nombre')" />
            <flux:input wire:model="direccion" :label="__('Dirección')" />
            <flux:input wire:model="cuit" :label="__('CUIT')" placeholder="20-12345678-9" />
            <flux:input wire:model="referente" :label="__('Referente')" />
            <flux:input wire:model="capacidad" :label="__('Capacidad')" type="number" min="1" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
