<?php

namespace App\Livewire\Economato;

use App\Concerns\MenuSemanalValidationRules;
use App\Enums\AccionPermiso;
use App\Enums\DiaSemana;
use App\Enums\Modulo;
use App\Enums\MomentoComida;
use App\Models\ItemDeMenu;
use App\Models\MenuSemanal;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Historial de menús semanales: cada fila es una semana, con su estado de
 * aprobación. Crear un menú nuevo arma las 20 casillas vacías (5 días ×
 * 4 momentos de alimentación) y abre el editor de esa semana.
 */
#[Title('Menús semanales')]
class Menus extends Component
{
    use AuthorizesRequests, MenuSemanalValidationRules, WithPagination;

    public string $semanaInicio = '';

    public function mount(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Ver));
    }

    /**
     * @return LengthAwarePaginator<int, MenuSemanal>
     */
    #[Computed]
    public function menus(): LengthAwarePaginator
    {
        return MenuSemanal::query()->orderByDesc('semana_inicio')->paginate(10);
    }

    public function nuevo(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $this->semanaInicio = Carbon::now()->addWeek()->startOfWeek()->format('Y-m-d');
        $this->resetErrorBag();
    }

    public function crear(): void
    {
        $this->authorize(Modulo::Economato->permiso(AccionPermiso::Crear));

        $datos = $this->validate($this->menuSemanalRules());

        $menu = MenuSemanal::create(['semana_inicio' => $datos['semanaInicio']]);

        foreach (DiaSemana::cases() as $dia) {
            foreach (MomentoComida::cases() as $comida) {
                ItemDeMenu::create([
                    'menu_semanal_id' => $menu->id,
                    'dia' => $dia,
                    'comida' => $comida,
                ]);
            }
        }

        $this->redirect(route('economato.menus.editar', $menu), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.economato.menus');
    }
}
