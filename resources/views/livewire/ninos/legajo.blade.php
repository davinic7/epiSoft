<div x-data="{ pestana: 'personales' }" class="flex flex-col gap-6">
    <div class="flex items-center justify-between print:hidden">
        <div>
            <flux:heading size="xl">
                {{ __('Legajo de :nombre', ['nombre' => $this->nino->nombres.' '.$this->nino->apellidos]) }}
            </flux:heading>
            <flux:subheading>{{ __('Vista de consulta e impresión.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="primary" icon="printer" x-on:click="window.print()">
                {{ __('Imprimir') }}
            </flux:button>

            <flux:button variant="ghost" icon="arrow-left" :href="route('ninos.editar', $this->nino)" wire:navigate>
                {{ __('Volver al legajo editable') }}
            </flux:button>
        </div>
    </div>

    <div class="hidden print:block">
        <flux:heading size="xl">
            {{ __('Legajo de :nombre', ['nombre' => $this->nino->nombres.' '.$this->nino->apellidos]) }}
        </flux:heading>
    </div>

    <div class="flex flex-wrap gap-2 print:hidden">
        @foreach ([
            'personales' => __('Datos personales'),
            'referentes' => __('Referentes'),
            'salud' => __('Salud'),
            'asistencia' => __('Asistencia'),
            'seguimiento' => __('Seguimiento'),
        ] as $clave => $etiqueta)
            <button
                type="button"
                x-on:click="pestana = '{{ $clave }}'"
                x-bind:class="pestana === '{{ $clave }}'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                class="cursor-pointer rounded-full px-3 py-1 text-sm font-medium transition-colors"
            >
                {{ $etiqueta }}
            </button>
        @endforeach
    </div>

    {{-- Datos personales --}}
    <div x-show="pestana === 'personales'" class="print:!block space-y-4">
        <flux:heading size="lg" class="hidden print:block">{{ __('Datos personales') }}</flux:heading>

        <div class="grid max-w-2xl grid-cols-2 gap-x-8 gap-y-2">
            <flux:text variant="subtle">{{ __('Nombres') }}</flux:text>
            <flux:text>{{ $this->nino->nombres }}</flux:text>

            <flux:text variant="subtle">{{ __('Apellidos') }}</flux:text>
            <flux:text>{{ $this->nino->apellidos }}</flux:text>

            <flux:text variant="subtle">{{ __('Cómo le dicen') }}</flux:text>
            <flux:text>{{ $this->nino->alias ?: '—' }}</flux:text>

            <flux:text variant="subtle">{{ __('DNI') }}</flux:text>
            <flux:text>{{ $this->nino->dni }}</flux:text>

            <flux:text variant="subtle">{{ __('Fecha de nacimiento') }}</flux:text>
            <flux:text>{{ $this->nino->fecha_nacimiento->format('d/m/Y') }}</flux:text>

            <flux:text variant="subtle">{{ __('Lugar de nacimiento') }}</flux:text>
            <flux:text>{{ $this->nino->lugar_nacimiento ?: '—' }}</flux:text>

            <flux:text variant="subtle">{{ __('Domicilio') }}</flux:text>
            <flux:text>{{ $this->nino->domicilio }}</flux:text>

            <flux:text variant="subtle">{{ __('Sala') }}</flux:text>
            <flux:text>{{ $this->nino->sala?->nombre ?? __('Sin asignar') }}</flux:text>

            <flux:text variant="subtle">{{ __('Fecha de ingreso') }}</flux:text>
            <flux:text>{{ $this->nino->fecha_ingreso->format('d/m/Y') }}</flux:text>

            @if ($this->nino->observaciones)
                <flux:text variant="subtle">{{ __('Observaciones') }}</flux:text>
                <flux:text>{{ $this->nino->observaciones }}</flux:text>
            @endif
        </div>
    </div>

    {{-- Referentes --}}
    <div x-show="pestana === 'referentes'" class="print:!block space-y-4">
        <flux:heading size="lg" class="hidden print:block">{{ __('Referentes') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Apellido y nombre') }}</flux:table.column>
                <flux:table.column>{{ __('DNI') }}</flux:table.column>
                <flux:table.column>{{ __('Teléfono') }}</flux:table.column>
                <flux:table.column>{{ __('Parentesco') }}</flux:table.column>
                <flux:table.column>{{ __('Autorizado a retirar') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->nino->referentes as $referente)
                    <flux:table.row wire:key="referente-{{ $referente->id }}">
                        <flux:table.cell>{{ $referente->apellidos }}, {{ $referente->nombres }}</flux:table.cell>
                        <flux:table.cell>{{ $referente->dni }}</flux:table.cell>
                        <flux:table.cell>{{ $referente->telefono }}</flux:table.cell>
                        <flux:table.cell>{{ $referente->pivot->parentesco }}</flux:table.cell>
                        <flux:table.cell>{{ $referente->pivot->autorizado_a_retirar ? __('Sí') : __('No') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text>{{ __('Sin referentes cargados.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Salud: alergias y vacunas --}}
    <div x-show="pestana === 'salud'" class="print:!block space-y-6">
        <flux:heading size="lg" class="hidden print:block">{{ __('Salud') }}</flux:heading>

        <div>
            <flux:heading size="base">{{ __('Alergias y restricciones alimentarias') }}</flux:heading>

            @if ($this->nino->alergias->isEmpty())
                <flux:text variant="subtle">{{ __('Sin registrar.') }}</flux:text>
            @else
                <ul class="list-inside list-disc">
                    @foreach ($this->nino->alergias as $alergia)
                        <li>
                            {{ $alergia->descripcion }}
                            <span class="capitalize">({{ $alergia->severidad->value }})</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <flux:heading size="base">{{ __('Carnet de vacunación') }}</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Vacuna') }}</flux:table.column>
                    <flux:table.column>{{ __('Dosis') }}</flux:table.column>
                    <flux:table.column>{{ __('Estado') }}</flux:table.column>
                    <flux:table.column>{{ __('Fecha de aplicación') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->estadosVacunas as $dosis)
                        <flux:table.row wire:key="dosis-{{ $dosis['clave'] }}">
                            <flux:table.cell>{{ $dosis['nombre'] }}</flux:table.cell>
                            <flux:table.cell>{{ $dosis['dosis'] }}</flux:table.cell>
                            <flux:table.cell>
                                {{ match ($dosis['estado']) {
                                    'aplicada' => __('Aplicada'),
                                    'atrasada' => __('Atrasada'),
                                    default => __('Pendiente'),
                                } }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $dosis['fecha_aplicacion']?->format('d/m/Y') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- Asistencia del mes --}}
    <div x-show="pestana === 'asistencia'" class="print:!block space-y-4">
        <flux:heading size="lg" class="hidden print:block">{{ __('Asistencia del mes') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column>{{ __('Hora de ingreso') }}</flux:table.column>
                <flux:table.column>{{ __('Hora de egreso') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->asistenciaDelMes as $dia)
                    <flux:table.row wire:key="asistencia-{{ $dia['fecha']->format('Y-m-d') }}">
                        <flux:table.cell>{{ $dia['fecha']->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            {{ match ($dia['estado']) {
                                'presente' => __('Presente'),
                                'ausente' => __('Ausente'),
                                default => __('Sin registro'),
                            } }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $dia['hora_ingreso'] }}</flux:table.cell>
                        <flux:table.cell>{{ $dia['hora_egreso'] }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Seguimiento pedagógico --}}
    <div x-show="pestana === 'seguimiento'" class="print:!block space-y-4">
        <flux:heading size="lg" class="hidden print:block">{{ __('Seguimiento') }}</flux:heading>

        <flux:callout icon="information-circle" :heading="__('Todavía no disponible')">
            {{ __('El seguimiento pedagógico individual se agrega en un milestone posterior.') }}
        </flux:callout>
    </div>
</div>
