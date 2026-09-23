<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Importar niños') }}</flux:heading>
            <flux:subheading>{{ __('Carga masiva de legajos desde una planilla CSV.') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('ninos.index')" wire:navigate>
            {{ __('Volver al listado') }}
        </flux:button>
    </div>

    <flux:callout icon="information-circle" :heading="__('Cómo armar la planilla')">
        {{ __('Columnas obligatorias: nombres, apellidos, dni, fecha_nacimiento, domicilio, fecha_ingreso. Columnas opcionales: alias, lugar_nacimiento, sala. Las fechas van en formato AAAA-MM-DD.') }}
    </flux:callout>

    <div>
        <flux:button size="sm" variant="ghost" icon="arrow-down-tray" wire:click="descargarPlantilla">
            {{ __('Descargar plantilla') }}
        </flux:button>
    </div>

    <form wire:submit="importar" class="max-w-xl space-y-4">
        <flux:input type="file" wire:model="archivo" :label="__('Planilla CSV')" accept=".csv,text/csv" />

        <flux:button type="submit" variant="primary" icon="arrow-up-tray">
            {{ __('Importar') }}
        </flux:button>
    </form>

    @if ($resultado !== null)
        @php
            $importadas = collect($resultado)->where('estado', 'importada')->count();
            $rechazadas = collect($resultado)->where('estado', 'rechazada')->count();
        @endphp

        <flux:callout
            :variant="$rechazadas > 0 ? 'warning' : 'success'"
            :heading="__(':importadas importadas, :rechazadas rechazadas.', ['importadas' => $importadas, 'rechazadas' => $rechazadas])"
        />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fila') }}</flux:table.column>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Motivo') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($resultado as $fila)
                    <flux:table.row wire:key="fila-{{ $fila['fila'] }}">
                        <flux:table.cell>{{ $fila['fila'] }}</flux:table.cell>
                        <flux:table.cell>{{ $fila['nombre'] }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($fila['estado'] === 'importada')
                                <flux:badge color="green" size="sm">{{ __('Importada') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __('Rechazada') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $fila['motivo'] }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
