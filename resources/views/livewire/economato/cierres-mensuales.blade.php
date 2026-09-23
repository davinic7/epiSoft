<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Cierre mensual de economato') }}</flux:heading>
            <flux:subheading>{{ __('Cerrar un período bloquea nuevos movimientos con fecha en ese mes.') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" icon="archive-box" :href="route('economato.articulos.index')" wire:navigate>
            {{ __('Catálogo') }}
        </flux:button>
    </div>

    <div class="flex items-end gap-4">
        <flux:input wire:model.live="periodoACerrar" :label="__('Período')" type="month" class="max-w-48" />

        @can('economato.editar')
            @if ($this->periodoYaCerrado)
                <flux:badge color="zinc">{{ __('Ya cerrado') }}</flux:badge>
            @else
                <flux:button
                    variant="primary"
                    icon="lock-closed"
                    wire:click="cerrarPeriodo"
                    wire:confirm="{{ __('¿Cerrar este período? Va a bloquear nuevos movimientos con fecha en ese mes.') }}"
                >
                    {{ __('Cerrar período') }}
                </flux:button>
            @endif
        @endcan
    </div>

    <div>
        <flux:heading size="lg">{{ __('Saldos del período') }}</flux:heading>

        <flux:table class="mt-2">
            <flux:table.columns>
                <flux:table.column>{{ __('Artículo') }}</flux:table.column>
                <flux:table.column>{{ __('Saldo inicial') }}</flux:table.column>
                <flux:table.column>{{ __('Saldo final') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->reporteDeCierre as $fila)
                    <flux:table.row wire:key="articulo-{{ $fila['articulo']->id }}">
                        <flux:table.cell>{{ $fila['articulo']->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $fila['saldoInicial'] }} {{ $fila['articulo']->unidad_medida }}</flux:table.cell>
                        <flux:table.cell>{{ $fila['saldoFinal'] }} {{ $fila['articulo']->unidad_medida }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3">
                            <flux:text>{{ __('Todavía no hay artículos en el catálogo.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg">{{ __('Historial de cierres') }}</flux:heading>

        <flux:table class="mt-2">
            <flux:table.columns>
                <flux:table.column>{{ __('Período') }}</flux:table.column>
                <flux:table.column>{{ __('Cerrado por') }}</flux:table.column>
                <flux:table.column>{{ __('Cerrado el') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->cierres as $cierre)
                    <flux:table.row wire:key="cierre-{{ $cierre->id }}">
                        <flux:table.cell>{{ $cierre->periodo->format('m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $cierre->cerradoPor->name }}</flux:table.cell>
                        <flux:table.cell>{{ $cierre->cerrado_en->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($cierre->reabierto_en)
                                <flux:badge color="zinc" size="sm">{{ __('Reabierto') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __('Cerrado') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if (! $cierre->reabierto_en)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="lock-open"
                                    wire:click="reabrir({{ $cierre->id }})"
                                    wire:confirm="{{ __('¿Reabrir este período?') }}"
                                >
                                    {{ __('Reabrir') }}
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text>{{ __('Todavía no se cerró ningún período.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->cierres->links() }}
    </div>
</div>
