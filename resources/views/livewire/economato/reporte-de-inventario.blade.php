<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between print:hidden">
        <div>
            <flux:heading size="xl">{{ __('Inventario por ubicación') }}</flux:heading>
            <flux:subheading>{{ __('Bienes patrimoniales agrupados por su ubicación actual.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" icon="arrow-left" :href="route('economato.bienes.index')" wire:navigate>
                {{ __('Volver al inventario') }}
            </flux:button>

            <flux:button variant="primary" icon="printer" x-on:click="window.print()">
                {{ __('Imprimir') }}
            </flux:button>
        </div>
    </div>

    <flux:heading size="lg" class="hidden print:block">{{ __('Inventario por ubicación') }}</flux:heading>

    <div class="flex flex-col gap-6">
        @forelse ($this->bienesPorUbicacion as $ubicacion => $bienes)
            <div>
                <flux:heading size="lg">{{ $ubicacion }}</flux:heading>

                <flux:table class="mt-2">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Código') }}</flux:table.column>
                        <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                        <flux:table.column>{{ __('Estado') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($bienes as $bien)
                            <flux:table.row wire:key="bien-{{ $bien->id }}">
                                <flux:table.cell>{{ $bien->codigo }}</flux:table.cell>
                                <flux:table.cell>{{ $bien->nombre }}</flux:table.cell>
                                <flux:table.cell class="capitalize">{{ $bien->estado_conservacion->value }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @empty
            <flux:text>{{ __('Todavía no hay bienes cargados.') }}</flux:text>
        @endforelse
    </div>
</div>
