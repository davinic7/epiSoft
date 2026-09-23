<?php

namespace App\Livewire\Economato;

use App\Enums\AccionPermiso;
use App\Enums\DiaSemana;
use App\Enums\EquipoTecnicoProvincial;
use App\Enums\EstadoMenu;
use App\Enums\Modulo;
use App\Enums\MomentoComida;
use App\Models\ItemDeMenu;
use App\Models\MenuSemanal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Carga del menú de una semana, celda por día y por momento de
 * alimentación. Aprobarlo es una acción reservada al equipo técnico de
 * nutrición (guía, cap. 2, pág. 16; ver
 * ProvisionadorDeRolesProvinciales), no al permiso general de editar
 * economato: un encargado de economato puede armar el borrador, pero no
 * autoaprobarlo.
 */
#[Title('Menú semanal')]
class MenuEditor extends Component
{
    use AuthorizesRequests;

    public int $menuId;

    public string $semanaInicioFormateada = '';

    public EstadoMenu $estado;

    public bool $soloLectura = false;

    /**
     * @var array<string, string>
     */
    public array $descripciones = [];

    public function mount(MenuSemanal $menu): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));

        $this->menuId = $menu->id;
        $this->semanaInicioFormateada = $menu->semana_inicio->format('d/m/Y');
        $this->estado = $menu->estado;
        $this->soloLectura = $menu->estado === EstadoMenu::Aprobado
            || ! Gate::allows(Modulo::Economato->permiso(AccionPermiso::Editar));

        foreach ($menu->items as $item) {
            $this->descripciones[$this->clave($item->dia, $item->comida)] = (string) $item->descripcion;
        }
    }

    /**
     * @return array<int, DiaSemana>
     */
    public function dias(): array
    {
        return DiaSemana::cases();
    }

    /**
     * @return array<int, MomentoComida>
     */
    public function comidas(): array
    {
        return MomentoComida::cases();
    }

    public function clave(DiaSemana $dia, MomentoComida $comida): string
    {
        return $dia->value.'|'.$comida->value;
    }

    public function guardar(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Editar));

        $menu = MenuSemanal::findOrFail($this->menuId);

        if ($menu->estado === EstadoMenu::Aprobado) {
            Flux::toast(variant: 'danger', text: __('Un menú aprobado no se puede editar.'));

            return;
        }

        foreach (ItemDeMenu::where('menu_semanal_id', $menu->id)->get() as $item) {
            $item->update(['descripcion' => $this->descripciones[$this->clave($item->dia, $item->comida)] ?? '']);
        }

        Flux::toast(variant: 'success', text: __('Menú guardado.'));
    }

    public function aprobar(): void
    {
        abort_unless(auth()->user()->hasRole(EquipoTecnicoProvincial::Nutricion->value), 403);

        $menu = MenuSemanal::findOrFail($this->menuId);

        if ($menu->estado === EstadoMenu::Aprobado) {
            Flux::toast(variant: 'danger', text: __('Ese menú ya está aprobado.'));

            return;
        }

        $menu->aprobar(auth()->user());

        $this->estado = EstadoMenu::Aprobado;
        $this->soloLectura = true;

        Flux::toast(variant: 'success', text: __('Menú aprobado.'));
    }

    public function render(): View
    {
        return view('livewire.economato.menu-editor');
    }
}
