<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Auditoría') }}</flux:heading>
                <flux:subheading>{{ __('Quién hizo qué, sobre qué registro y desde dónde.') }}</flux:subheading>
            </div>

            <flux:select wire:model.live="evento" class="max-w-48" :aria-label="__('Evento')">
                <flux:select.option value="">{{ __('Todos los eventos') }}</flux:select.option>
                <flux:select.option value="created">{{ __('Alta') }}</flux:select.option>
                <flux:select.option value="updated">{{ __('Modificación') }}</flux:select.option>
                <flux:select.option value="deleted">{{ __('Baja') }}</flux:select.option>
                <flux:select.option value="restored">{{ __('Restauración') }}</flux:select.option>
                <flux:select.option value="acceso">{{ __('Acceso') }}</flux:select.option>
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Usuario') }}</flux:table.column>
                <flux:table.column>{{ __('Acción') }}</flux:table.column>
                <flux:table.column>{{ __('Entidad') }}</flux:table.column>
                <flux:table.column>{{ __('IP') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->registros as $registro)
                    <flux:table.row wire:key="registro-{{ $registro->id }}">
                        <flux:table.cell>{{ $registro->created_at->format('d/m/Y H:i:s') }}</flux:table.cell>
                        <flux:table.cell>{{ $registro->usuario?->name ?? __('Sistema') }}</flux:table.cell>
                        <flux:table.cell>{{ $registro->etiquetaEvento() }}</flux:table.cell>
                        <flux:table.cell>{{ $registro->etiquetaEntidad() }} #{{ $registro->auditable_id }}</flux:table.cell>
                        <flux:table.cell>{{ $registro->ip_address }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text>{{ __('Todavía no hay registros de auditoría.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->registros->links() }}
    </div>
</div>
