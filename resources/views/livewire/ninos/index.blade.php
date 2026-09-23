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
                    <flux:button variant="ghost" icon="arrow-up-tray" :href="route('ninos.importar')" wire:navigate>
                        {{ __('Importar') }}
                    </flux:button>

                    <flux:button variant="primary" icon="plus" :href="route('ninos.crear')" wire:navigate>
                        {{ __('Nuevo legajo') }}
                    </flux:button>
                @endcan
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <flux:input
                wire:model.live.debounce.400ms="busqueda"
                :label="__('Buscar')"
                placeholder="Nombre, alias o DNI"
                icon="magnifying-glass"
                class="max-w-64"
            />

            <flux:select wire:model.live="salaId" :label="__('Sala')" class="max-w-48">
                <flux:select.option value="">{{ __('Todas') }}</flux:select.option>
                @foreach ($this->salas as $sala)
                    <flux:select.option value="{{ $sala->id }}">{{ $sala->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="turno" :label="__('Turno')" class="max-w-40">
                <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                @foreach ($this->turnos as $opcion)
                    <flux:select.option value="{{ $opcion->value }}" class="capitalize">
                        {{ $opcion->value }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="estado" :label="__('Estado')" class="max-w-40">
                <flux:select.option value="activos">{{ __('Activos') }}</flux:select.option>
                <flux:select.option value="baja">{{ __('De baja') }}</flux:select.option>
                <flux:select.option value="todos">{{ __('Todos') }}</flux:select.option>
            </flux:select>
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
                            @if ($nino->trashed())
                                <flux:badge color="zinc" size="sm">{{ __('De baja') }}</flux:badge>
                                <div>
                                    <flux:text size="sm" variant="subtle">
                                        {{ __('Baja el :fecha — :motivo', [
                                            'fecha' => $nino->fecha_baja?->format('d/m/Y'),
                                            'motivo' => $nino->motivo_baja,
                                        ]) }}
                                    </flux:text>
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $nino->dni }}</flux:table.cell>
                        <flux:table.cell>{{ $nino->fecha_nacimiento->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $nino->sala?->nombre ?? __('Sin asignar') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="identification"
                                    :href="route('ninos.legajo', $nino)"
                                    wire:navigate
                                >
                                    {{ __('Legajo') }}
                                </flux:button>

                                @unless ($nino->trashed())
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                        :href="route('ninos.editar', $nino)"
                                        wire:navigate
                                    >
                                        {{ __('Editar') }}
                                    </flux:button>
                                @endunless

                                @can('ninos.eliminar')
                                    @if ($nino->trashed())
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="arrow-uturn-left"
                                            wire:click="restaurar({{ $nino->id }})"
                                            wire:confirm="{{ __('¿Restaurar a :nombre?', ['nombre' => $nino->nombres]) }}"
                                        >
                                            {{ __('Restaurar') }}
                                        </flux:button>
                                    @else
                                        <flux:modal.trigger name="formulario-baja">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="arrow-right-start-on-rectangle"
                                                wire:click="iniciarBaja({{ $nino->id }})"
                                            >
                                                {{ __('Dar de baja') }}
                                            </flux:button>
                                        </flux:modal.trigger>
                                    @endif
                                @endcan
                            </div>
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

    <flux:modal name="formulario-baja" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="guardarBaja" class="space-y-6">
            <flux:heading size="lg">{{ __('Dar de baja') }}</flux:heading>

            <flux:input wire:model="fechaBaja" :label="__('Fecha de baja')" type="date" />
            <flux:textarea wire:model="motivoBaja" :label="__('Motivo')" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="danger">{{ __('Confirmar baja') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
