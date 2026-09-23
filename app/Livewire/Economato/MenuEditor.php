<?php

namespace App\Livewire\Economato;

use App\Enums\AccionPermiso;
use App\Enums\DiaSemana;
use App\Enums\EquipoTecnicoProvincial;
use App\Enums\EstadoMenu;
use App\Enums\Modulo;
use App\Enums\MomentoComida;
use App\Models\Alergia;
use App\Models\ItemDeMenu;
use App\Models\MenuSemanal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
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

    /**
     * Niños cuya alergia o restricción aparece como texto dentro de la
     * descripción de alguna casilla del menú, para advertir antes de
     * aprobar. Es una coincidencia de texto simple (no hay una lista
     * estructurada de ingredientes ni en el menú ni en la alergia), así
     * que avisa, no bloquea: el equipo de nutrición decide con esa
     * información.
     *
     * @return array<int, array{dia: DiaSemana, comida: MomentoComida, alergia: Alergia}>
     */
    #[Computed]
    public function advertenciasDeAlergias(): array
    {
        $alergias = Alergia::query()->with('nino')->get();

        $advertencias = [];

        foreach ($this->dias() as $dia) {
            foreach ($this->comidas() as $comida) {
                $descripcion = $this->descripciones[$this->clave($dia, $comida)] ?? '';

                if (trim($descripcion) === '') {
                    continue;
                }

                foreach ($alergias as $alergia) {
                    if (trim($alergia->descripcion) !== '' && mb_stripos($descripcion, $alergia->descripcion) !== false) {
                        $advertencias[] = ['dia' => $dia, 'comida' => $comida, 'alergia' => $alergia];
                    }
                }
            }
        }

        return $advertencias;
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
