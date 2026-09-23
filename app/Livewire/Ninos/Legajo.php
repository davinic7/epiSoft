<?php

namespace App\Livewire\Ninos;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Services\CalendarioDeVacunacion;
use App\Services\ReporteDeAsistencia;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Vista de consulta e impresión del legajo completo, con los datos reales
 * del niño reunidos en pestañas: datos personales, referentes, salud
 * (alergias y vacunas) y asistencia del mes. El seguimiento pedagógico
 * individual todavía no existe (ver el issue "Seguimientos individuales"
 * de M3), así que esa pestaña queda como pendiente.
 *
 * Es de solo lectura: dar de alta o editar cada sección sigue haciéndose
 * en sus propias pantallas (Formulario, Referentes, Alergias, Vacunas).
 */
#[Title('Legajo completo')]
class Legajo extends Component
{
    use AuthorizesRequests;

    public int $ninoId;

    public function mount(int $nino): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Ver));

        $modelo = Nino::findOrFail($nino);
        $modelo->registrarAcceso();

        $this->ninoId = $modelo->id;
    }

    #[Computed]
    public function nino(): Nino
    {
        return Nino::with(['sala', 'referentes', 'alergias'])->findOrFail($this->ninoId);
    }

    /**
     * @return Collection<int, array{clave: string, nombre: string, dosis: string, edad_meses: int, estado: string, fecha_aplicacion: ?Carbon}>
     */
    #[Computed]
    public function estadosVacunas(): Collection
    {
        return app(CalendarioDeVacunacion::class)->estadoPorNino($this->nino());
    }

    /**
     * @return Collection<int, array{fecha: Carbon, estado: string, hora_ingreso: ?string, hora_egreso: ?string}>
     */
    #[Computed]
    public function asistenciaDelMes(): Collection
    {
        return app(ReporteDeAsistencia::class)->porNino($this->nino(), Carbon::today());
    }

    public function render(): View
    {
        return view('livewire.ninos.legajo');
    }
}
