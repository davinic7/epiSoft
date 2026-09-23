<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between print:hidden">
        <div>
            <flux:heading size="xl">{{ __('Restricciones por sala') }}</flux:heading>
            <flux:subheading>{{ __('Alergias y restricciones alimentarias vigentes, para cocina.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" icon="archive-box" :href="route('economato.articulos.index')" wire:navigate>
                {{ __('Catálogo') }}
            </flux:button>

            <flux:button variant="primary" icon="printer" x-on:click="window.print()">
                {{ __('Imprimir') }}
            </flux:button>
        </div>
    </div>

    <flux:heading size="lg" class="hidden print:block">{{ __('Restricciones por sala') }}</flux:heading>

    <div class="flex flex-col gap-6">
        @foreach ($this->salas as $sala)
            @php $ninos = $this->ninosConAlergias($sala); @endphp

            @if ($ninos->isNotEmpty())
                <div>
                    <flux:heading size="lg">{{ $sala->nombre }}</flux:heading>

                    <flux:table class="mt-2">
                        <flux:table.columns>
                            <flux:table.column>{{ __('Niño/a') }}</flux:table.column>
                            <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                            <flux:table.column>{{ __('Severidad') }}</flux:table.column>
                            <flux:table.column>{{ __('Descripción') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($ninos as $nino)
                                @foreach ($nino->alergias as $alergia)
                                    <flux:table.row wire:key="alergia-{{ $alergia->id }}">
                                        <flux:table.cell>{{ $nino->apellidos }}, {{ $nino->nombres }}</flux:table.cell>
                                        <flux:table.cell class="capitalize">{{ $alergia->tipo->value }}</flux:table.cell>
                                        <flux:table.cell class="capitalize">{{ $alergia->severidad->value }}</flux:table.cell>
                                        <flux:table.cell>{{ $alergia->descripcion }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        @endforeach

        @if ($this->salas->every(fn ($sala) => $this->ninosConAlergias($sala)->isEmpty()))
            <flux:text>{{ __('Ninguna sala tiene alergias o restricciones cargadas.') }}</flux:text>
        @endif
    </div>
</div>
