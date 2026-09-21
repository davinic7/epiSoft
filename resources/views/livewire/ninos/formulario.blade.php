<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">
                {{ $ninoId ? __('Legajo de :nombre', ['nombre' => $nombres.' '.$apellidos]) : __('Nuevo legajo') }}
            </flux:heading>
            <flux:subheading>
                @if ($soloLectura)
                    {{ __('Consulta de solo lectura.') }}
                @else
                    {{ __('Paso :actual de 3.', ['actual' => $paso]) }}
                @endif
            </flux:subheading>
        </div>

        <div class="flex gap-2">
            @if ($ninoId)
                <flux:button variant="ghost" icon="users" :href="route('ninos.referentes', $ninoId)" wire:navigate>
                    {{ __('Referentes') }}
                </flux:button>

                <flux:button variant="ghost" icon="beaker" :href="route('ninos.vacunas', $ninoId)" wire:navigate>
                    {{ __('Vacunas') }}
                </flux:button>
            @endif

            <flux:button variant="ghost" icon="arrow-left" :href="route('ninos.index')" wire:navigate>
                {{ __('Volver al listado') }}
            </flux:button>
        </div>
    </div>

    <div class="flex gap-2">
        @foreach ([1 => __('Identificación'), 2 => __('Domicilio'), 3 => __('Institucional')] as $numero => $etiqueta)
            <flux:badge
                :variant="$paso === $numero ? 'solid' : 'outline'"
                :color="$paso === $numero ? 'blue' : 'zinc'"
                wire:click="irAPaso({{ $numero }})"
                class="cursor-pointer"
            >
                {{ $numero }}. {{ $etiqueta }}
            </flux:badge>
        @endforeach
    </div>

    <form wire:submit="guardar" class="max-w-xl space-y-6">
        @if ($paso === 1)
            <flux:input wire:model="nombres" :label="__('Nombres')" :disabled="$soloLectura" />
            <flux:input wire:model="apellidos" :label="__('Apellidos')" :disabled="$soloLectura" />
            <flux:input wire:model="alias" :label="__('Cómo le dicen')" :disabled="$soloLectura" />
            <flux:input wire:model="dni" :label="__('DNI')" :disabled="$soloLectura" />
            <flux:input wire:model="fechaNacimiento" :label="__('Fecha de nacimiento')" type="date" :disabled="$soloLectura" />
            <flux:input wire:model="lugarNacimiento" :label="__('Lugar de nacimiento')" :disabled="$soloLectura" />
        @elseif ($paso === 2)
            <flux:textarea wire:model="domicilio" :label="__('Domicilio actual')" rows="3" :disabled="$soloLectura" />
        @else
            <flux:select wire:model="salaId" :label="__('Sala')" :disabled="$soloLectura">
                <flux:select.option value="">{{ __('Sin asignar') }}</flux:select.option>
                @foreach ($this->salas as $sala)
                    <flux:select.option value="{{ $sala->id }}">{{ $sala->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="fechaIngreso" :label="__('Fecha de ingreso')" type="date" :disabled="$soloLectura" />
            <flux:textarea wire:model="observaciones" :label="__('Observaciones')" rows="3" :disabled="$soloLectura" />
        @endif

        <div class="flex justify-between">
            <flux:button type="button" variant="filled" wire:click="anterior" :disabled="$paso === 1">
                {{ __('Atrás') }}
            </flux:button>

            @if ($paso < 3)
                <flux:button type="button" variant="primary" wire:click="siguiente">
                    {{ __('Siguiente') }}
                </flux:button>
            @elseif (! $soloLectura)
                <flux:button type="submit" variant="primary">
                    {{ __('Guardar') }}
                </flux:button>
            @endif
        </div>
    </form>
</div>
