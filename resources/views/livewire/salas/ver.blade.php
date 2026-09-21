<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $this->sala->nombre }}</flux:heading>
            <flux:subheading class="capitalize">
                {{ __('Turno :turno · Capacidad :capacidad · :cantidad niños', [
                    'turno' => $this->sala->turno->value,
                    'capacidad' => $this->sala->capacidad,
                    'cantidad' => $this->ninos->count(),
                ]) }}
            </flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('salas.index')" wire:navigate>
            {{ __('Volver a salas') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Apellido y nombre') }}</flux:table.column>
            <flux:table.column>{{ __('Fecha de nacimiento') }}</flux:table.column>
            <flux:table.column>{{ __('Alergias y restricciones') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->ninos as $nino)
                <flux:table.row wire:key="nino-{{ $nino->id }}">
                    <flux:table.cell>
                        <a href="{{ route('ninos.editar', $nino) }}" wire:navigate class="hover:underline">
                            {{ $nino->apellidos }}, {{ $nino->nombres }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $nino->fecha_nacimiento->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($nino->alergias->isEmpty())
                            <flux:text variant="subtle">{{ __('Sin registrar') }}</flux:text>
                        @else
                            <div class="flex flex-wrap gap-1">
                                @foreach ($nino->alergias as $alergia)
                                    <flux:badge :color="match ($alergia->severidad) {
                                        \App\Enums\SeveridadAlergia::Grave => 'red',
                                        \App\Enums\SeveridadAlergia::Moderada => 'yellow',
                                        \App\Enums\SeveridadAlergia::Leve => 'zinc',
                                    }">
                                        {{ $alergia->descripcion }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <flux:text>{{ __('Todavía no hay niños asignados a esta sala.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
