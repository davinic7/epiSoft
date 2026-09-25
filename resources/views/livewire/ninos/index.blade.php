<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Niños') }}</flux:heading>
                <flux:subheading>{{ __('Legajos de los niños y niñas de la institución.') }}</flux:subheading>
            </div>

            @can('create', \App\Models\Nino::class)
                <flux:modal.trigger name="formulario-nino">
                    <flux:button variant="primary" icon="plus" wire:click="nuevo">
                        {{ __('Nuevo legajo') }}
                    </flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Apellido y nombre') }}</flux:table.column>
                <flux:table.column>{{ __('DNI') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha de nacimiento') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->ninos as $nino)
                    <flux:table.row wire:key="nino-{{ $nino->id }}">
                        <flux:table.cell>{{ $nino->nombreCompleto() }}</flux:table.cell>
                        <flux:table.cell>{{ $nino->dni }}</flux:table.cell>
                        <flux:table.cell>{{ $nino->fecha_nacimiento->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('ninos.show', $nino)" wire:navigate>
                                    {{ __('Legajo') }}
                                </flux:button>

                                @can('update', $nino)
                                    <flux:modal.trigger name="formulario-nino">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                            wire:click="editar({{ $nino->id }})"
                                        >
                                            {{ __('Editar') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endcan

                                @can('delete', $nino)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="eliminar({{ $nino->id }})"
                                        wire:confirm="{{ __('¿Eliminar el legajo de :nombre? Esta acción no se puede deshacer.', ['nombre' => $nino->nombreCompleto()]) }}"
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
                            <flux:text>{{ __('Todavía no hay legajos cargados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->ninos->links() }}
    </div>

    <flux:modal name="formulario-nino" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $ninoId ? __('Editar legajo') : __('Nuevo legajo') }}
            </flux:heading>

            <flux:input wire:model="apellido" :label="__('Apellido')" />
            <flux:input wire:model="nombre" :label="__('Nombre')" />
            <flux:input wire:model="dni" :label="__('DNI')" inputmode="numeric" maxlength="8" />
            <flux:input wire:model="fecha_nacimiento" :label="__('Fecha de nacimiento')" type="date" />
            <flux:input wire:model="domicilio" :label="__('Domicilio')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
