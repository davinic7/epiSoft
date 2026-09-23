<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Menú de la semana del :fecha', ['fecha' => $semanaInicioFormateada]) }}</flux:heading>
            <flux:subheading>
                @if ($soloLectura && $estado->value === 'aprobado')
                    {{ __('Aprobado. Ya no se puede editar.') }}
                @elseif ($soloLectura)
                    {{ __('Consulta de solo lectura.') }}
                @else
                    {{ __('Borrador.') }}
                @endif
            </flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" icon="arrow-left" :href="route('economato.menus.index')" wire:navigate>
                {{ __('Volver al historial') }}
            </flux:button>

            @can('economato.editar')
                @if ($estado->value !== 'aprobado')
                    <flux:button
                        variant="primary"
                        icon="check"
                        wire:click="aprobar"
                        wire:confirm="{{ __('¿Aprobar este menú? Ya no se va a poder editar.') }}"
                    >
                        {{ __('Aprobar') }}
                    </flux:button>
                @endif
            @endcan
        </div>
    </div>

    <form wire:submit="guardar">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-2">
                <thead>
                    <tr>
                        <th></th>
                        @foreach ($this->dias() as $dia)
                            <th class="text-start text-sm font-medium capitalize">{{ $dia->value }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->comidas() as $comida)
                        <tr>
                            <th class="text-start text-sm font-medium capitalize">{{ $comida->value }}</th>
                            @foreach ($this->dias() as $dia)
                                <td>
                                    <flux:textarea
                                        wire:model="descripciones.{{ $this->clave($dia, $comida) }}"
                                        rows="2"
                                        :disabled="$soloLectura"
                                    />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @unless ($soloLectura)
            <div class="mt-4">
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        @endunless
    </form>
</div>
