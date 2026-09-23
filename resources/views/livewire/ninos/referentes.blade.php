<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">
                {{ __('Referentes de :nombre', ['nombre' => $this->nino->nombres.' '.$this->nino->apellidos]) }}
            </flux:heading>
            <flux:subheading>
                {{ __('Quiénes acompañan a este niño y quiénes están autorizados a retirarlo.') }}
            </flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('ninos.editar', $this->nino)" wire:navigate>
            {{ __('Volver al legajo') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Apellido y nombre') }}</flux:table.column>
            <flux:table.column>{{ __('DNI') }}</flux:table.column>
            <flux:table.column>{{ __('Teléfono') }}</flux:table.column>
            <flux:table.column>{{ __('Parentesco') }}</flux:table.column>
            <flux:table.column>{{ __('Autorizado a retirar') }}</flux:table.column>
            @unless ($soloLectura)
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            @endunless
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->referentesVinculados as $referente)
                <flux:table.row wire:key="referente-{{ $referente->id }}">
                    <flux:table.cell>{{ $referente->apellidos }}, {{ $referente->nombres }}</flux:table.cell>
                    <flux:table.cell>{{ $referente->dni }}</flux:table.cell>
                    <flux:table.cell>{{ $referente->telefono }}</flux:table.cell>
                    <flux:table.cell>{{ $referente->pivot->parentesco }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$referente->pivot->autorizado_a_retirar ? 'green' : 'zinc'">
                            {{ $referente->pivot->autorizado_a_retirar ? __('Sí') : __('No') }}
                        </flux:badge>
                    </flux:table.cell>
                    @unless ($soloLectura)
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:modal.trigger name="editar-vinculo">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                        wire:click="editarVinculo({{ $referente->id }})"
                                    >
                                        {{ __('Editar vínculo') }}
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="x-mark"
                                    wire:click="quitar({{ $referente->id }})"
                                    wire:confirm="{{ __('¿Quitar a :nombre como referente de este niño?', ['nombre' => $referente->nombres]) }}"
                                >
                                    {{ __('Quitar') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    @endunless
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ $soloLectura ? 5 : 6 }}">
                        <flux:text>{{ __('Todavía no hay referentes cargados.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @unless ($soloLectura)
        <div class="max-w-xl space-y-6">
            <flux:heading size="lg">{{ __('Agregar referente') }}</flux:heading>

            <form wire:submit="agregar" class="space-y-6">
                <flux:input wire:model="dni" wire:blur="buscarPorDni" :label="__('DNI')" />
                <flux:input wire:model="nombres" :label="__('Nombres')" />
                <flux:input wire:model="apellidos" :label="__('Apellidos')" />
                <flux:input wire:model="telefono" :label="__('Teléfono')" />
                <flux:input wire:model="domicilio" :label="__('Domicilio')" />
                <flux:input wire:model="parentesco" :label="__('Parentesco')" placeholder="Madre, padre, abuela..." />
                <flux:checkbox wire:model="autorizadoARetirar" :label="__('Autorizado a retirar al niño')" />

                <flux:button type="submit" variant="primary">
                    {{ __('Agregar') }}
                </flux:button>
            </form>
        </div>

        <flux:modal name="editar-vinculo" :show="$errors->has('parentescoEdicion')" class="md:w-96">
            <form wire:submit="guardarVinculo" class="space-y-6">
                <flux:heading size="lg">{{ __('Editar vínculo') }}</flux:heading>

                <flux:input wire:model="parentescoEdicion" :label="__('Parentesco')" />
                <flux:checkbox wire:model="autorizadoEdicion" :label="__('Autorizado a retirar al niño')" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endunless
</div>
