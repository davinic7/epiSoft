<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <flux:heading size="xl">{{ $sala->nombre }}</flux:heading>

                @if ($sala->descripcion)
                    <flux:subheading>{{ $sala->descripcion }}</flux:subheading>
                @endif

                <div class="flex flex-wrap gap-2">
                    <flux:badge size="sm">{{ $sala->turno->etiqueta() }}</flux:badge>

                    <flux:badge size="sm" :color="$this->ninos->count() > $sala->capacidad ? 'red' : 'zinc'">
                        {{ __(':cantidad de :capacidad lugares', ['cantidad' => $this->ninos->count(), 'capacidad' => $sala->capacidad]) }}
                    </flux:badge>

                    @if ($this->rangoDeEdades)
                        <flux:badge size="sm" color="sky">{{ __('Edades: :rango', ['rango' => $this->rangoDeEdades]) }}</flux:badge>
                    @endif
                </div>
            </div>

            <flux:button icon="arrow-left" :href="route('salas.index')" wire:navigate>
                {{ __('Volver a salas') }}
            </flux:button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:text>{{ __('Educadoras:') }}</flux:text>

            @forelse ($this->educadoras as $educadora)
                <flux:badge size="sm" icon="user" wire:key="educadora-{{ $educadora->id }}">{{ $educadora->name }}</flux:badge>
            @empty
                <flux:badge size="sm" color="amber">{{ __('Sin educadoras asignadas') }}</flux:badge>
            @endforelse

            @can('update', $sala)
                <flux:modal.trigger name="educadoras-sala">
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editarEducadoras">
                        {{ __('Cambiar') }}
                    </flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        @can('update', $sala)
            <div class="flex flex-wrap items-end gap-2">
                <flux:modal.trigger name="agregar-ninos">
                    <flux:button variant="primary" icon="plus">{{ __('Agregar niños') }}</flux:button>
                </flux:modal.trigger>

                @if ($this->ninos->isNotEmpty())
                    <flux:select wire:model="salaDestinoId" :placeholder="__('Pasar marcados a…')" class="max-w-56">
                        @foreach ($this->otrasSalas as $otra)
                            <flux:select.option :value="$otra->id" wire:key="destino-{{ $otra->id }}">{{ $otra->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button icon="arrow-right" wire:click="mover">{{ __('Mover') }}</flux:button>

                    <flux:button
                        variant="ghost"
                        icon="x-mark"
                        wire:click="quitar"
                        wire:confirm="{{ __('¿Quitar de la sala a los niños marcados? Quedan sin sala asignada.') }}"
                    >
                        {{ __('Quitar de la sala') }}
                    </flux:button>
                @endif
            </div>

            <flux:error name="seleccionados" />
            <flux:error name="salaDestinoId" />
        @endcan

        <div class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($this->ninos as $nino)
                <div class="flex items-center gap-3 px-4 py-3" wire:key="nino-{{ $nino->id }}">
                    @can('update', $sala)
                        <flux:checkbox wire:model="seleccionados" :value="$nino->id" />
                    @endcan

                    <div class="min-w-0 flex-1">
                        <flux:link :href="route('ninos.show', $nino)" wire:navigate>{{ $nino->nombreCompleto() }}</flux:link>
                    </div>

                    <flux:text class="whitespace-nowrap">{{ $nino->edadLegible() }}</flux:text>
                </div>
            @empty
                <div class="px-4 py-3">
                    <flux:text>{{ __('Todavía no hay niños en esta sala.') }}</flux:text>
                </div>
            @endforelse
        </div>
    </div>

    @can('update', $sala)
        <flux:modal name="educadoras-sala" class="w-full md:w-96">
            <form wire:submit="guardarEducadoras" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Educadoras de :sala', ['sala' => $sala->nombre]) }}</flux:heading>
                    <flux:text>{{ __('Marcá quiénes están a cargo de esta sala. Una educadora puede estar en más de una sala.') }}</flux:text>
                </div>

                <div class="max-h-80 space-y-3 overflow-y-auto">
                    @forelse ($this->educadorasDisponibles as $educadora)
                        <flux:checkbox
                            wire:model="educadorasElegidas"
                            :value="$educadora->id"
                            :label="$educadora->name"
                            wire:key="disponible-{{ $educadora->id }}"
                        />
                    @empty
                        <flux:text>{{ __('No hay personas con rol de educador en esta institución.') }}</flux:text>
                    @endforelse
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="agregar-ninos" class="w-full md:w-[28rem]">
            <form wire:submit="agregar" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Agregar niños a :sala', ['sala' => $sala->nombre]) }}</flux:heading>
                    <flux:text>{{ __('Solo se listan los niños que todavía no tienen sala.') }}</flux:text>
                </div>

                <flux:input wire:model.live.debounce.300ms="busqueda" icon="magnifying-glass" :placeholder="__('Buscar por apellido, nombre o DNI')" />

                <div class="max-h-80 space-y-3 overflow-y-auto">
                    @forelse ($this->ninosSinSala as $nino)
                        <div class="flex items-center justify-between gap-3" wire:key="sin-sala-{{ $nino->id }}">
                            <flux:checkbox wire:model="paraAgregar" :value="$nino->id" :label="$nino->nombreCompleto()" />
                            <flux:text class="whitespace-nowrap">{{ $nino->edadLegible() }}</flux:text>
                        </div>
                    @empty
                        <flux:text>{{ __('No hay niños sin sala.') }}</flux:text>
                    @endforelse
                </div>

                <flux:error name="paraAgregar" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">{{ __('Agregar') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
