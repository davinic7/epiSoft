<?php

namespace App\Livewire\Economato;

use App\Concerns\BienValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\EstadoConservacion;
use App\Enums\Modulo;
use App\Models\Bien;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Alta y listado del inventario patrimonial (guía, cap. 3.4, "Libro de
 * registro de inventario institucional", pág. 24). La ubicación no se
 * edita desde el formulario general: cambiarla es una acción aparte
 * (mover()) que deja constancia en el historial de movimientos.
 */
#[Title('Inventario patrimonial')]
class Bienes extends Component
{
    use AuthorizesRequests, BienValidationRules, WithPagination;

    public ?int $bienId = null;

    public string $nombre = '';

    public string $codigo = '';

    public string $ubicacion = '';

    public string $estadoConservacion = '';

    public ?int $bienAMoverId = null;

    public string $ubicacionNueva = '';

    public string $fechaMovimiento = '';

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));
    }

    /**
     * @return LengthAwarePaginator<int, Bien>
     */
    #[Computed]
    public function bienes(): LengthAwarePaginator
    {
        return Bien::query()->orderBy('nombre')->paginate(15);
    }

    /**
     * @return array<int, EstadoConservacion>
     */
    #[Computed]
    public function estados(): array
    {
        return EstadoConservacion::cases();
    }

    public function nuevo(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $this->reset(['bienId', 'nombre', 'codigo', 'ubicacion', 'estadoConservacion']);
        $this->resetErrorBag();
    }

    public function editar(int $bienId): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        $bien = Bien::findOrFail($bienId);

        $this->bienId = $bien->id;
        $this->nombre = $bien->nombre;
        $this->codigo = $bien->codigo;
        $this->ubicacion = $bien->ubicacion;
        $this->estadoConservacion = $bien->estado_conservacion->value;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $this->authorize(Modulo::Economato->permiso($this->bienId ? AccionPermiso::Editar : AccionPermiso::Crear));

        $datos = $this->validate($this->bienRules($this->bienId));

        $atributos = [
            'nombre' => $datos['nombre'],
            'codigo' => $datos['codigo'],
            'estado_conservacion' => $datos['estadoConservacion'],
        ];

        if ($this->bienId) {
            Bien::findOrFail($this->bienId)->update($atributos);
        } else {
            Bien::create($atributos + ['ubicacion' => $datos['ubicacion']]);
        }

        $this->js("\$flux.modal('formulario-bien').close()");

        Flux::toast(variant: 'success', text: __('Bien guardado.'));
    }

    public function nuevoMovimiento(int $bienId): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        $this->bienAMoverId = $bienId;
        $this->ubicacionNueva = '';
        $this->fechaMovimiento = Carbon::today()->format('Y-m-d');
        $this->resetErrorBag();
    }

    public function moverUbicacion(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        $datos = $this->validate($this->movimientoDeUbicacionRules());

        Bien::findOrFail($this->bienAMoverId)->moverA($datos['ubicacionNueva'], Carbon::parse($datos['fechaMovimiento']));

        $this->js("\$flux.modal('formulario-movimiento-ubicacion').close()");

        Flux::toast(variant: 'success', text: __('Ubicación actualizada.'));
    }

    public function render(): View
    {
        return view('livewire.economato.bienes');
    }
}
