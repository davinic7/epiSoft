<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Niños') }}</flux:heading>
                <flux:subheading>{{ __('Legajos de niñas y niños de la institución.') }}</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" icon="calendar-days" :href="route('ninos.reporte-asistencia')" wire:navigate>
                    {{ __('Asistencia') }}
                </flux:button>

                <flux:button variant="ghost" icon="beaker" :href="route('ninos.vacunas-atrasadas')" wire:navigate>
                    {{ __('Vacunas atrasadas') }}
                </flux:button>

                @can('ninos.crear')
                    <flux:button variant="primary" icon="plus" :href="route('ninos.crear')" wire:navigate>
                        {{ __('Nuevo legajo') }}
                    </flux:button>
                @endcan
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Apellido y nombre') }}</flux:table.column>
                <flux:table.column>{{ __('DNI') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha de nacimiento') }}</flux:table.column>
                <flux:table.column>{{ __('Sala') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->ninos as $nino)
                    <flux:table.row wire:key="nino-{{ $nino->id }}">
                        <flux:table.cell>
                            {{ $nino->apellidos }}, {{ $nino->nombres }}
                            @if ($nino->alias)
                                <flux:text class="inline" size="sm" variant="subtle">({{ $nino->alias }})</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $nino->dni }}</flux:table.cell>
                        <flux:table.cell>{{ $nino->fecha_nacimiento->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $nino->sala?->nombre ?? __('Sin asignar') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                                :href="route('ninos.editar', $nino)"
                                wire:navigate
                            >
                                {{ __('Ver / editar') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text>{{ __('Todavía no hay niños cargados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->ninos->links() }}
    </div>
</div>
