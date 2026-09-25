<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $nino->nombreCompleto() }}</flux:heading>
            <flux:subheading>{{ __('Legajo del niño o niña.') }}</flux:subheading>
        </div>

        <flux:button icon="arrow-left" :href="route('ninos.index')" wire:navigate>
            {{ __('Volver al listado') }}
        </flux:button>
    </div>

    <dl class="grid max-w-xl grid-cols-[max-content_1fr] gap-x-6 gap-y-3">
        <dt><flux:text>{{ __('DNI') }}</flux:text></dt>
        <dd><flux:heading>{{ $nino->dni }}</flux:heading></dd>

        <dt><flux:text>{{ __('Fecha de nacimiento') }}</flux:text></dt>
        <dd><flux:heading>{{ $nino->fecha_nacimiento->format('d/m/Y') }}</flux:heading></dd>

        <dt><flux:text>{{ __('Edad') }}</flux:text></dt>
        <dd><flux:heading>{{ $nino->edadLegible() }}</flux:heading></dd>

        <dt><flux:text>{{ __('Sala') }}</flux:text></dt>
        <dd><flux:heading>{{ $nino->sala?->nombre ?? __('Sin sala asignada') }}</flux:heading></dd>

        <dt><flux:text>{{ __('Domicilio') }}</flux:text></dt>
        <dd><flux:heading>{{ $nino->domicilio ?: '—' }}</flux:heading></dd>
    </dl>
</div>
