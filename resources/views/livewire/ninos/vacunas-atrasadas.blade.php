<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Vacunas atrasadas') }}</flux:heading>
        <flux:subheading>
            {{ __('Niños con al menos una dosis del calendario nacional vencida según su edad.') }}
        </flux:subheading>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Apellido y nombre') }}</flux:table.column>
            <flux:table.column>{{ __('DNI') }}</flux:table.column>
            <flux:table.column>{{ __('Fecha de nacimiento') }}</flux:table.column>
            <flux:table.column>{{ __('Acciones') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->ninos as $nino)
                <flux:table.row wire:key="nino-{{ $nino->id }}">
                    <flux:table.cell>{{ $nino->apellidos }}, {{ $nino->nombres }}</flux:table.cell>
                    <flux:table.cell>{{ $nino->dni }}</flux:table.cell>
                    <flux:table.cell>{{ $nino->fecha_nacimiento->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="pencil"
                            :href="route('ninos.vacunas', $nino)"
                            wire:navigate
                        >
                            {{ __('Ver carnet') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <flux:text>{{ __('Ningún niño tiene vacunas atrasadas.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
