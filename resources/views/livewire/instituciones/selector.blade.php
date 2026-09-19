<div>
    @php($actual = $this->instituciones->firstWhere('id', $this->activa))

    @if ($this->instituciones->count() > 1)
        <flux:dropdown position="bottom" align="start" class="w-full">
            <flux:button variant="filled" icon="building-office-2" icon-trailing="chevron-up-down" class="w-full justify-between">
                <span class="truncate">{{ $actual?->nombre ?? __('Elegir institución') }}</span>
            </flux:button>

            <flux:menu>
                @foreach ($this->instituciones as $institucion)
                    <flux:menu.item
                        wire:key="institucion-{{ $institucion->id }}"
                        wire:click="cambiar({{ $institucion->id }})"
                        :icon="$institucion->id === $this->activa ? 'check' : null"
                    >
                        {{ $institucion->nombre }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    @else
        <div class="flex items-center gap-2 px-2 text-sm text-zinc-500 dark:text-zinc-400">
            <flux:icon name="building-office-2" class="size-4" />
            <span class="truncate">{{ $actual?->nombre ?? __('Sin institución') }}</span>
        </div>
    @endif
</div>
