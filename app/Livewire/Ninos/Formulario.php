<?php

namespace App\Livewire\Ninos;

use App\Concerns\NinoValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Models\Sala;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Alta y edición de un legajo de niño/a en tres pasos: identificación,
 * domicilio y datos institucionales. Los referentes, la salud y las
 * vacunas se cargan aparte, sobre el legajo ya creado (ver los issues
 * "Referentes y vínculo familiar", "Alergias y restricciones
 * alimentarias" y "Carnet de vacunación" del backlog).
 *
 * Un usuario con permiso de solo lectura (ninos.ver sin ninos.editar,
 * como el coordinador pedagógico) puede abrir un legajo existente para
 * consultarlo, pero no guardar cambios.
 */
#[Title('Legajo de niño/a')]
class Formulario extends Component
{
    use AuthorizesRequests, NinoValidationRules;

    public ?int $ninoId = null;

    public bool $soloLectura = false;

    public int $paso = 1;

    // Paso 1: identificación
    public string $nombres = '';

    public string $apellidos = '';

    public string $alias = '';

    public string $dni = '';

    public string $fechaNacimiento = '';

    public string $lugarNacimiento = '';

    // Paso 2: domicilio
    public string $domicilio = '';

    // Paso 3: datos institucionales
    public ?int $salaId = null;

    public string $fechaIngreso = '';

    public string $observaciones = '';

    public function mount(?int $nino = null): void
    {
        if ($nino === null) {
            $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Crear));

            return;
        }

        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $modelo = Nino::findOrFail($nino);
        $modelo->registrarAcceso();

        $this->soloLectura = ! Gate::allows(Modulo::Ninos->permiso(AccionPermiso::Editar));

        $this->ninoId = $modelo->id;
        $this->nombres = $modelo->nombres;
        $this->apellidos = $modelo->apellidos;
        $this->alias = (string) $modelo->alias;
        $this->dni = $modelo->dni;
        $this->fechaNacimiento = $modelo->fecha_nacimiento->format('Y-m-d');
        $this->lugarNacimiento = (string) $modelo->lugar_nacimiento;
        $this->domicilio = $modelo->domicilio;
        $this->salaId = $modelo->sala_id;
        $this->fechaIngreso = $modelo->fecha_ingreso->format('Y-m-d');
        $this->observaciones = (string) $modelo->observaciones;
    }

    /**
     * @return Collection<int, Sala>
     */
    #[Computed]
    public function salas(): Collection
    {
        return Sala::query()->orderBy('nombre')->get();
    }

    /**
     * Valida el paso actual y avanza al siguiente.
     */
    public function siguiente(): void
    {
        $this->validate($this->reglasPaso($this->paso, $this->ninoId));

        $this->paso = min($this->paso + 1, 3);
    }

    public function anterior(): void
    {
        $this->paso = max($this->paso - 1, 1);
    }

    /**
     * Permite volver directamente a un paso anterior ya completado. No deja
     * saltar hacia adelante sin pasar por siguiente(), que valida cada paso.
     */
    public function irAPaso(int $paso): void
    {
        if ($paso < $this->paso) {
            $this->paso = $paso;
        }
    }

    /**
     * Crea o actualiza el legajo con los datos de los tres pasos.
     */
    public function guardar(): void
    {
        $this->authorize(Modulo::Ninos->permiso($this->ninoId ? AccionPermiso::Editar : AccionPermiso::Crear));

        $datos = $this->validate($this->ninoRules($this->ninoId));

        $atributos = [
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'alias' => $datos['alias'] ?: null,
            'dni' => $datos['dni'],
            'fecha_nacimiento' => $datos['fechaNacimiento'],
            'lugar_nacimiento' => $datos['lugarNacimiento'] ?: null,
            'domicilio' => $datos['domicilio'],
            'sala_id' => $datos['salaId'],
            'fecha_ingreso' => $datos['fechaIngreso'],
            'observaciones' => $datos['observaciones'] ?: null,
        ];

        if ($this->ninoId) {
            Nino::findOrFail($this->ninoId)->update($atributos);
        } else {
            Nino::create($atributos);
        }

        Flux::toast(variant: 'success', text: __('Legajo guardado.'));

        $this->redirect(route('ninos.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.ninos.formulario');
    }
}
