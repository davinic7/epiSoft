<?php

namespace App\Livewire\Ninos;

use App\Concerns\VacunaAplicadaValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Models\VacunaAplicada;
use App\Services\CalendarioDeVacunacion;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Carnet de vacunación de un niño: estado de cada dosis del calendario
 * nacional (ver App\Services\CalendarioDeVacunacion) y registro de las
 * aplicadas, con fecha.
 */
#[Title('Carnet de vacunación')]
class Vacunas extends Component
{
    use AuthorizesRequests, VacunaAplicadaValidationRules;

    public int $ninoId;

    public bool $soloLectura = false;

    public string $vacunaClave = '';

    public string $fechaAplicacion = '';

    public string $observaciones = '';

    public function mount(int $nino): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $modelo = Nino::findOrFail($nino);
        $modelo->registrarAcceso();

        $this->ninoId = $modelo->id;
        $this->soloLectura = ! Gate::allows(Modulo::Ninos->permiso(AccionPermiso::Editar));
    }

    #[Computed]
    public function nino(): Nino
    {
        return Nino::findOrFail($this->ninoId);
    }

    /**
     * @return Collection<int, array{clave: string, nombre: string, dosis: string, edad_meses: int, estado: string, fecha_aplicacion: ?Carbon}>
     */
    #[Computed]
    public function estados(): Collection
    {
        return app(CalendarioDeVacunacion::class)->estadoPorNino($this->nino());
    }

    /**
     * Dosis del calendario que todavía no fueron registradas, para no
     * ofrecer en el selector una que ya tiene fecha de aplicación.
     *
     * @return Collection<int, array{clave: string, nombre: string, dosis: string, edad_meses: int, estado: string, fecha_aplicacion: ?Carbon}>
     */
    #[Computed]
    public function dosisPendientesDeRegistro(): Collection
    {
        return $this->estados()->reject(fn (array $dosis) => $dosis['estado'] === 'aplicada');
    }

    /**
     * @return EloquentCollection<int, VacunaAplicada>
     */
    #[Computed]
    public function aplicadas(): EloquentCollection
    {
        return $this->nino()->vacunasAplicadas()->orderByDesc('fecha_aplicacion')->get();
    }

    public function registrar(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Editar));

        $datos = $this->validate($this->vacunaAplicadaRules());

        if ($this->nino()->vacunasAplicadas()->where('vacuna_clave', $datos['vacunaClave'])->exists()) {
            $this->addError('vacunaClave', __('Esta dosis ya está registrada.'));

            return;
        }

        $this->nino()->vacunasAplicadas()->create([
            'vacuna_clave' => $datos['vacunaClave'],
            'fecha_aplicacion' => $datos['fechaAplicacion'],
            'observaciones' => $datos['observaciones'] ?: null,
        ]);

        $this->reset(['vacunaClave', 'fechaAplicacion', 'observaciones']);
        unset($this->estados, $this->dosisPendientesDeRegistro, $this->aplicadas);

        Flux::toast(variant: 'success', text: __('Vacuna registrada.'));
    }

    public function quitar(int $vacunaAplicadaId): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Editar));

        $this->nino()->vacunasAplicadas()->whereKey($vacunaAplicadaId)->delete();

        unset($this->estados, $this->dosisPendientesDeRegistro, $this->aplicadas);

        Flux::toast(variant: 'success', text: __('Registro eliminado.'));
    }

    public function render(): View
    {
        return view('livewire.ninos.vacunas');
    }
}
