<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Reporte de asistencia') }}</flux:heading>
        <flux:subheading>{{ __('Asistencia mensual por niño o por sala.') }}</flux:subheading>
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <flux:select wire:model.live="modo" :label="__('Ver por')" class="max-w-40">
            <flux:select.option value="nino">{{ __('Niño') }}</flux:select.option>
            <flux:select.option value="sala">{{ __('Sala') }}</flux:select.option>
        </flux:select>

        @if ($modo === 'nino')
            <flux:select wire:model.live="ninoId" :label="__('Niño')" class="max-w-64">
                <flux:select.option value="">{{ __('Seleccionar niño') }}</flux:select.option>
                @foreach ($this->ninos as $nino)
                    <flux:select.option value="{{ $nino->id }}">{{ $nino->apellidos }}, {{ $nino->nombres }}</flux:select.option>
                @endforeach
            </flux:select>
        @else
            <flux:select wire:model.live="salaId" :label="__('Sala')" class="max-w-64">
                <flux:select.option value="">{{ __('Seleccionar sala') }}</flux:select.option>
                @foreach ($this->salas as $sala)
                    <flux:select.option value="{{ $sala->id }}">{{ $sala->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:input wire:model.live="mes" :label="__('Mes')" type="month" class="max-w-40" />
    </div>

    @if ($modo === 'nino' && $this->reportePorNino !== null)
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Hora de ingreso') }}</flux:table.column>
                <flux:table.column>{{ __('Hora de egreso') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->reportePorNino as $dia)
                    <flux:table.row wire:key="dia-{{ $dia['fecha']->format('Y-m-d') }}">
                        <flux:table.cell>{{ $dia['fecha']->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match ($dia['estado']) {
                                'presente' => 'green',
                                'ausente' => 'red',
                                default => 'zinc',
                            }">
                                {{ match ($dia['estado']) {
                                    'presente' => __('Presente'),
                                    'ausente' => __('Ausente'),
                                    default => __('Sin registro'),
                                } }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $dia['hora_ingreso'] }}</flux:table.cell>
                        <flux:table.cell>{{ $dia['hora_egreso'] }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @elseif ($modo === 'sala' && $this->reportePorSala !== null)
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Niño') }}</flux:table.column>
                <flux:table.column>{{ __('Días presente') }}</flux:table.column>
                <flux:table.column>{{ __('Días con registro') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->reportePorSala as $fila)
                    <flux:table.row wire:key="fila-{{ $fila['nino']->id }}">
                        <flux:table.cell>{{ $fila['nino']->apellidos }}, {{ $fila['nino']->nombres }}</flux:table.cell>
                        <flux:table.cell>{{ $fila['dias_presente'] }}</flux:table.cell>
                        <flux:table.cell>{{ $fila['dias_registrados'] }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3">
                            <flux:text>{{ __('Esta sala no tiene niños asignados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    @else
        <flux:text variant="subtle">{{ __('Elegí un niño o una sala para ver el reporte.') }}</flux:text>
    @endif
</div>
