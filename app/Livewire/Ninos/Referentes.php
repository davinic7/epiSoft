<?php

namespace App\Livewire\Ninos;

use App\Concerns\ReferenteValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Models\Referente;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Referentes afectivos de un niño: alta (reutilizando el referente si ya
 * está cargado por DNI, para no duplicarlo entre hermanos), parentesco,
 * autorización de retiro y baja del vínculo.
 *
 * Gestionar referentes exige ninos.crear o ninos.editar: el encargado de
 * recepción, que solo tiene ninos.crear, es quien conoce a los referentes
 * autorizados a retirar a cada niño y registra esas novedades (guía, cap.
 * 2.2 y Anexo 1, Ficha 1) aunque no edite el resto del legajo.
 */
#[Title('Referentes')]
class Referentes extends Component
{
    use AuthorizesRequests, ReferenteValidationRules;

    public int $ninoId;

    public bool $soloLectura = false;

    // Alta de referente y vínculo
    public string $dni = '';

    public string $nombres = '';

    public string $apellidos = '';

    public string $telefono = '';

    public string $domicilio = '';

    public string $parentesco = '';

    public bool $autorizadoARetirar = false;

    // Edición de un vínculo existente
    public ?int $referenteEnEdicionId = null;

    public string $parentescoEdicion = '';

    public bool $autorizadoEdicion = false;

    public function mount(int $nino): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $modelo = Nino::findOrFail($nino);
        $modelo->registrarAcceso();

        $this->ninoId = $modelo->id;
        $this->soloLectura = ! Gate::any([
            Modulo::Ninos->permiso(AccionPermiso::Crear),
            Modulo::Ninos->permiso(AccionPermiso::Editar),
        ]);
    }

    #[Computed]
    public function nino(): Nino
    {
        return Nino::findOrFail($this->ninoId);
    }

    /**
     * @return Collection<int, Referente>
     */
    #[Computed]
    public function referentesVinculados(): Collection
    {
        return $this->nino()->referentes()->orderBy('apellidos')->get();
    }

    /**
     * Precarga los datos de un referente ya cargado en la institución, para
     * no duplicarlo si ya está vinculado a otro niño.
     */
    public function buscarPorDni(): void
    {
        $existente = Referente::query()->where('dni', $this->dni)->first();

        if ($existente) {
            $this->nombres = $existente->nombres;
            $this->apellidos = $existente->apellidos;
            $this->telefono = (string) $existente->telefono;
            $this->domicilio = (string) $existente->domicilio;
        }
    }

    /**
     * Vincula un referente al niño, reutilizando el registro existente por
     * DNI dentro de la institución en vez de duplicarlo.
     */
    public function agregar(): void
    {
        $this->autorizarGestion();

        $datosReferente = $this->validate($this->referenteRules());
        $datosVinculo = $this->validate($this->vinculoRules());

        $referente = Referente::query()->firstOrCreate(
            ['dni' => $datosReferente['dni']],
            [
                'nombres' => $datosReferente['nombres'],
                'apellidos' => $datosReferente['apellidos'],
                'telefono' => $datosReferente['telefono'] ?: null,
                'domicilio' => $datosReferente['domicilio'] ?: null,
            ],
        );

        if ($this->nino()->referentes()->where('referente_id', $referente->id)->exists()) {
            $this->addError('dni', __('Este referente ya está vinculado a este niño.'));

            return;
        }

        $this->nino()->referentes()->attach($referente->id, [
            'parentesco' => $datosVinculo['parentesco'],
            'autorizado_a_retirar' => $datosVinculo['autorizadoARetirar'],
        ]);

        $this->reset(['dni', 'nombres', 'apellidos', 'telefono', 'domicilio', 'parentesco', 'autorizadoARetirar']);
        unset($this->referentesVinculados);

        Flux::toast(variant: 'success', text: __('Referente vinculado.'));
    }

    /**
     * Carga el vínculo de un referente en el formulario de edición.
     */
    public function editarVinculo(int $referenteId): void
    {
        $this->autorizarGestion();

        $vinculo = $this->nino()->referentes()->where('referente_id', $referenteId)->firstOrFail();

        $this->referenteEnEdicionId = $referenteId;
        $this->parentescoEdicion = $vinculo->pivot->parentesco;
        $this->autorizadoEdicion = $vinculo->pivot->autorizado_a_retirar;
    }

    public function guardarVinculo(): void
    {
        $this->autorizarGestion();

        $datos = $this->validate([
            'parentescoEdicion' => ['required', 'string', 'max:255'],
            'autorizadoEdicion' => ['boolean'],
        ]);

        $this->nino()->referentes()->updateExistingPivot($this->referenteEnEdicionId, [
            'parentesco' => $datos['parentescoEdicion'],
            'autorizado_a_retirar' => $datos['autorizadoEdicion'],
        ]);

        $this->js("\$flux.modal('editar-vinculo').close()");
        unset($this->referentesVinculados);

        Flux::toast(variant: 'success', text: __('Vínculo actualizado.'));
    }

    /**
     * Desvincula al referente de este niño sin borrar su registro: puede
     * seguir vinculado a otros hermanos.
     */
    public function quitar(int $referenteId): void
    {
        $this->autorizarGestion();

        $this->nino()->referentes()->detach($referenteId);

        unset($this->referentesVinculados);

        Flux::toast(variant: 'success', text: __('Referente desvinculado.'));
    }

    private function autorizarGestion(): void
    {
        abort_unless(Gate::any([
            Modulo::Ninos->permiso(AccionPermiso::Crear),
            Modulo::Ninos->permiso(AccionPermiso::Editar),
        ]), 403);
    }

    public function render(): View
    {
        return view('livewire.ninos.referentes');
    }
}
