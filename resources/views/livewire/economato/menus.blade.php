<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Menús semanales') }}</flux:heading>
                <flux:subheading>{{ __('Historial de menús, con su estado de aprobación.') }}</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" icon="archive-box" :href="route('economato.articulos.index')" wire:navigate>
                    {{ __('Catálogo') }}
                </flux:button>

                @can('economato.crear')
                    <flux:modal.trigger name="formulario-menu">
                        <flux:button variant="primary" icon="plus" wire:click="nuevo">
                            {{ __('Nuevo menú') }}
                        </flux:button>
                    </flux:modal.trigger>
                @endcan
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Semana del') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Aprobado por') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->menus as $menu)
                    <flux:table.row wire:key="menu-{{ $menu->id }}">
                        <flux:table.cell>{{ $menu->semana_inicio->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$menu->estado->value === 'aprobado' ? 'green' : 'zinc'" size="sm" class="capitalize">
                                {{ $menu->estado->value }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $menu->aprobadoPor?->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                                :href="route('economato.menus.editar', $menu)"
                                wire:navigate
                            >
                                {{ __('Ver') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <flux:text>{{ __('Todavía no hay menús cargados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->menus->links() }}
    </div>

    <flux:modal name="formulario-menu" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="crear" class="space-y-6">
            <flux:heading size="lg">{{ __('Nuevo menú') }}</flux:heading>

            <flux:input wire:model="semanaInicio" :label="__('Semana del (lunes)')" type="date" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Crear') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
