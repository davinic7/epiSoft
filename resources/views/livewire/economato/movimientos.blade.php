<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Libro de movimientos') }}</flux:heading>
                <flux:subheading>{{ __('Entradas y salidas de economato, con quién entrega y quién recibe.') }}</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" icon="cube" :href="route('economato.stock.index')" wire:navigate>
                    {{ __('Stock') }}
                </flux:button>

                <flux:button variant="ghost" icon="bell-alert" :href="route('economato.alertas.index')" wire:navigate>
                    {{ __('Alertas') }}
                </flux:button>

                @can('economato.crear')
                    <flux:modal.trigger name="formulario-salida">
                        <flux:button variant="primary" icon="minus" wire:click="nuevaSalida">
                            {{ __('Nueva salida') }}
                        </flux:button>
                    </flux:modal.trigger>
                @endcan
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Artículo') }}</flux:table.column>
                <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                <flux:table.column>{{ __('Cantidad') }}</flux:table.column>
                <flux:table.column>{{ __('Origen / destino') }}</flux:table.column>
                <flux:table.column>{{ __('Contraparte') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->movimientos as $movimiento)
                    <flux:table.row wire:key="movimiento-{{ $movimiento->id }}">
                        <flux:table.cell>{{ $movimiento->fecha->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $movimiento->lote->articulo->nombre }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$movimiento->tipo->value === 'entrada' ? 'green' : 'zinc'" size="sm" class="capitalize">
                                {{ $movimiento->tipo->value }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $movimiento->cantidad }} {{ $movimiento->lote->articulo->unidad_medida }}</flux:table.cell>
                        <flux:table.cell>{{ $movimiento->origen }}</flux:table.cell>
                        <flux:table.cell>{{ $movimiento->contraparte }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($movimiento->anula_a_id !== null)
                                <flux:badge color="zinc" size="sm">{{ __('Contramovimiento') }}</flux:badge>
                            @elseif ($movimiento->contramovimiento)
                                <flux:badge color="red" size="sm">{{ __('Anulado') }}</flux:badge>
                            @else
                                <flux:badge color="green" size="sm">{{ __('Vigente') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @can('economato.editar')
                                @if ($movimiento->anula_a_id === null && ! $movimiento->contramovimiento)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="x-circle"
                                        wire:click="anular({{ $movimiento->id }})"
                                        wire:confirm="{{ __('¿Anular este movimiento? Se va a registrar un contramovimiento de signo contrario.') }}"
                                    >
                                        {{ __('Anular') }}
                                    </flux:button>
                                @endif
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <flux:text>{{ __('Todavía no hay movimientos registrados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->movimientos->links() }}
    </div>

    <flux:modal name="formulario-salida" :show="$errors->isNotEmpty()" class="md:w-96">
        <form wire:submit="registrarSalida" class="space-y-6">
            <flux:heading size="lg">{{ __('Nueva salida') }}</flux:heading>

            <flux:select wire:model="articuloId" :label="__('Artículo')">
                <flux:select.option value="">{{ __('Seleccionar artículo') }}</flux:select.option>
                @foreach ($this->articulosParaSelect as $opcion)
                    <flux:select.option value="{{ $opcion->id }}">{{ $opcion->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="cantidad" :label="__('Cantidad')" type="number" step="0.01" min="0.01" />

            <flux:input wire:model="fecha" :label="__('Fecha de salida')" type="date" />

            <flux:input wire:model="origen" :label="__('Destino')" placeholder="Cocina / uso interno" />

            <flux:input wire:model="contraparte" :label="__('Quién recibe')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
