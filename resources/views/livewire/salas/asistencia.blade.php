<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">
                {{ __('Asistencia de :sala', ['sala' => $this->sala->nombre]) }}
            </flux:heading>
            <flux:subheading>{{ __('Toma de asistencia diaria por sala.') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('salas.ver', $salaId)" wire:navigate>
            {{ __('Volver a la sala') }}
        </flux:button>
    </div>

    <flux:input wire:model.live="fecha" :label="__('Fecha')" type="date" class="max-w-48" />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Niño') }}</flux:table.column>
            <flux:table.column>{{ __('Presente') }}</flux:table.column>
            <flux:table.column>{{ __('Hora de ingreso') }}</flux:table.column>
            <flux:table.column>{{ __('Hora de egreso') }}</flux:table.column>
            <flux:table.column>{{ __('Retirado por') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->ninos as $nino)
                <flux:table.row wire:key="fila-{{ $nino->id }}">
                    <flux:table.cell>{{ $nino->apellidos }}, {{ $nino->nombres }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:checkbox wire:model="filas.{{ $nino->id }}.presente" :disabled="$soloLectura" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:input
                            wire:model="filas.{{ $nino->id }}.horaIngreso"
                            type="time"
                            size="sm"
                            :disabled="$soloLectura"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:input
                            wire:model="filas.{{ $nino->id }}.horaEgreso"
                            type="time"
                            size="sm"
                            :disabled="$soloLectura"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:select
                            wire:model="filas.{{ $nino->id }}.retiradoPorId"
                            size="sm"
                            :disabled="$soloLectura"
                        >
                            <flux:select.option value="">{{ __('Sin especificar') }}</flux:select.option>
                            @foreach ($this->referentesAutorizados($nino->id) as $referente)
                                <flux:select.option value="{{ $referente->id }}">
                                    {{ $referente->nombres }} {{ $referente->apellidos }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <flux:text>{{ __('Esta sala todavía no tiene niños asignados.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @unless ($soloLectura)
        <div>
            <flux:button variant="primary" wire:click="guardar">
                {{ __('Guardar asistencia') }}
            </flux:button>
        </div>
    @endunless
</div>
