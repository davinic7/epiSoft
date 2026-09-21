<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">
                {{ __('Carnet de vacunación de :nombre', ['nombre' => $this->nino->nombres.' '.$this->nino->apellidos]) }}
            </flux:heading>
            <flux:subheading>
                {{ __('Calendario nacional de referencia: borrador sin validar contra la fuente oficial.') }}
            </flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('ninos.editar', $this->nino)" wire:navigate>
            {{ __('Volver al legajo') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Vacuna') }}</flux:table.column>
            <flux:table.column>{{ __('Dosis') }}</flux:table.column>
            <flux:table.column>{{ __('Edad correspondiente') }}</flux:table.column>
            <flux:table.column>{{ __('Estado') }}</flux:table.column>
            <flux:table.column>{{ __('Fecha de aplicación') }}</flux:table.column>
            @unless ($soloLectura)
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            @endunless
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->estados as $dosis)
                <flux:table.row wire:key="dosis-{{ $dosis['clave'] }}">
                    <flux:table.cell>{{ $dosis['nombre'] }}</flux:table.cell>
                    <flux:table.cell>{{ $dosis['dosis'] }}</flux:table.cell>
                    <flux:table.cell>{{ __(':meses meses', ['meses' => $dosis['edad_meses']]) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="match ($dosis['estado']) {
                            'aplicada' => 'green',
                            'atrasada' => 'red',
                            default => 'zinc',
                        }">
                            {{ match ($dosis['estado']) {
                                'aplicada' => __('Aplicada'),
                                'atrasada' => __('Atrasada'),
                                default => __('Pendiente'),
                            } }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $dosis['fecha_aplicacion']?->format('d/m/Y') }}</flux:table.cell>
                    @unless ($soloLectura)
                        <flux:table.cell>
                            @if ($dosis['estado'] === 'aplicada')
                                @php $aplicada = $this->aplicadas->firstWhere('vacuna_clave', $dosis['clave']) @endphp
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="x-mark"
                                    wire:click="quitar({{ $aplicada->id }})"
                                    wire:confirm="{{ __('¿Eliminar el registro de :vacuna?', ['vacuna' => $dosis['nombre']]) }}"
                                >
                                    {{ __('Quitar') }}
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    @endunless
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    @unless ($soloLectura)
        <div class="max-w-xl space-y-6">
            <flux:heading size="lg">{{ __('Registrar vacuna aplicada') }}</flux:heading>

            <form wire:submit="registrar" class="space-y-6">
                <flux:select wire:model="vacunaClave" :label="__('Vacuna')">
                    <flux:select.option value="">{{ __('Seleccionar dosis') }}</flux:select.option>
                    @foreach ($this->dosisPendientesDeRegistro as $dosis)
                        <flux:select.option value="{{ $dosis['clave'] }}">
                            {{ $dosis['nombre'] }} — {{ $dosis['dosis'] }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="fechaAplicacion" :label="__('Fecha de aplicación')" type="date" />
                <flux:textarea wire:model="observaciones" :label="__('Observaciones')" rows="3" />

                <flux:button type="submit" variant="primary">
                    {{ __('Registrar') }}
                </flux:button>
            </form>
        </div>
    @endunless
</div>
