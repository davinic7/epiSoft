<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">
                {{ __('Alergias de :nombre', ['nombre' => $this->nino->nombres.' '.$this->nino->apellidos]) }}
            </flux:heading>
            <flux:subheading>
                {{ __('Alergias y restricciones alimentarias registradas.') }}
            </flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('ninos.editar', $this->nino)" wire:navigate>
            {{ __('Volver al legajo') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Tipo') }}</flux:table.column>
            <flux:table.column>{{ __('Descripción') }}</flux:table.column>
            <flux:table.column>{{ __('Severidad') }}</flux:table.column>
            <flux:table.column>{{ __('Observaciones') }}</flux:table.column>
            @unless ($soloLectura)
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            @endunless
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->alergias as $alergia)
                <flux:table.row wire:key="alergia-{{ $alergia->id }}">
                    <flux:table.cell class="capitalize">{{ $alergia->tipo->value }}</flux:table.cell>
                    <flux:table.cell>{{ $alergia->descripcion }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="match ($alergia->severidad) {
                            \App\Enums\SeveridadAlergia::Grave => 'red',
                            \App\Enums\SeveridadAlergia::Moderada => 'yellow',
                            \App\Enums\SeveridadAlergia::Leve => 'zinc',
                        }" class="capitalize">
                            {{ $alergia->severidad->value }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $alergia->observaciones }}</flux:table.cell>
                    @unless ($soloLectura)
                        <flux:table.cell>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="x-mark"
                                wire:click="quitar({{ $alergia->id }})"
                                wire:confirm="{{ __('¿Eliminar este registro?') }}"
                            >
                                {{ __('Quitar') }}
                            </flux:button>
                        </flux:table.cell>
                    @endunless
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ $soloLectura ? 4 : 5 }}">
                        <flux:text>{{ __('Sin alergias ni restricciones registradas.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @unless ($soloLectura)
        <div class="max-w-xl space-y-6">
            <flux:heading size="lg">{{ __('Registrar alergia o restricción') }}</flux:heading>

            <form wire:submit="agregar" class="space-y-6">
                <flux:select wire:model="tipo" :label="__('Tipo')">
                    <flux:select.option value="">{{ __('Seleccionar tipo') }}</flux:select.option>
                    @foreach ($this->tipos as $opcion)
                        <flux:select.option value="{{ $opcion->value }}" class="capitalize">
                            {{ $opcion->value }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="descripcion" :label="__('Descripción')" placeholder="Maní, lactosa..." />

                <flux:select wire:model="severidad" :label="__('Severidad')">
                    <flux:select.option value="">{{ __('Seleccionar severidad') }}</flux:select.option>
                    @foreach ($this->severidades as $opcion)
                        <flux:select.option value="{{ $opcion->value }}" class="capitalize">
                            {{ $opcion->value }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="observaciones" :label="__('Observaciones')" rows="3" />

                <flux:button type="submit" variant="primary">
                    {{ __('Registrar') }}
                </flux:button>
            </form>
        </div>
    @endunless
</div>
