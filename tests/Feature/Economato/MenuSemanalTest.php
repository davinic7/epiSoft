<?php

use App\Enums\DiaSemana;
use App\Enums\EquipoTecnicoProvincial;
use App\Enums\EstadoMenu;
use App\Enums\MomentoComida;
use App\Enums\RolInstitucional;
use App\Livewire\Economato\MenuEditor;
use App\Livewire\Economato\Menus;
use App\Models\Institucion;
use App\Models\ItemDeMenu;
use App\Models\MenuSemanal;
use App\Models\User;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function usuarioNutricion(Institucion $institucion): User
{
    $usuario = User::factory()->create(['equipo_tecnico_provincial' => EquipoTecnicoProvincial::Nutricion]);
    $institucion->usuarios()->syncWithoutDetaching([$usuario->id]);

    $contexto = app(InstitucionContext::class);
    $activaPrevia = $contexto->id();
    $contexto->set($institucion->id);
    $usuario->assignRole(EquipoTecnicoProvincial::Nutricion->value);
    $contexto->set($activaPrevia);

    return $usuario;
}

test('un usuario sin permiso no puede acceder al historial de menús', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.menus.index'))
        ->assertForbidden();
});

test('crear un menú arma las casillas de los 5 días y las 4 comidas', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $lunes = Carbon::today()->next(Carbon::MONDAY);

    Livewire::actingAs($encargado)
        ->test(Menus::class)
        ->set('semanaInicio', $lunes->format('Y-m-d'))
        ->call('crear')
        ->assertHasNoErrors();

    $menu = MenuSemanal::whereDate('semana_inicio', $lunes->format('Y-m-d'))->sole();
    expect(ItemDeMenu::where('menu_semanal_id', $menu->id)->count())->toBe(count(DiaSemana::cases()) * count(MomentoComida::cases()))
        ->and($menu->estado)->toBe(EstadoMenu::Borrador);
});

test('la semana de un menú debe empezar un lunes', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $martes = Carbon::today()->next(Carbon::TUESDAY);

    Livewire::actingAs($encargado)
        ->test(Menus::class)
        ->set('semanaInicio', $martes->format('Y-m-d'))
        ->call('crear')
        ->assertHasErrors(['semanaInicio']);
});

test('no se permiten dos menús para la misma semana', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $lunes = Carbon::today()->next(Carbon::MONDAY);
    MenuSemanal::factory()->create(['semana_inicio' => $lunes]);

    Livewire::actingAs($encargado)
        ->test(Menus::class)
        ->set('semanaInicio', $lunes->format('Y-m-d'))
        ->call('crear')
        ->assertHasErrors(['semanaInicio']);
});

test('el encargado de economato puede cargar la descripción de cada casilla del menú', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $menu = MenuSemanal::factory()->create();
    foreach (DiaSemana::cases() as $dia) {
        foreach (MomentoComida::cases() as $comida) {
            ItemDeMenu::factory()->for($menu, 'menuSemanal')->create(['dia' => $dia, 'comida' => $comida, 'descripcion' => null]);
        }
    }

    Livewire::actingAs($encargado)
        ->test(MenuEditor::class, ['menu' => $menu->id])
        ->set('descripciones.lunes|desayuno', 'Leche con cereales')
        ->call('guardar')
        ->assertHasNoErrors();

    $item = ItemDeMenu::where('menu_semanal_id', $menu->id)
        ->where('dia', DiaSemana::Lunes)
        ->where('comida', MomentoComida::Desayuno)
        ->sole();

    expect($item->descripcion)->toBe('Leche con cereales');
});

test('un usuario sin el rol de nutrición no puede aprobar el menú', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $menu = MenuSemanal::factory()->create();

    Livewire::actingAs($encargado)
        ->test(MenuEditor::class, ['menu' => $menu->id])
        ->call('aprobar')
        ->assertForbidden();

    expect($menu->fresh()->estado)->toBe(EstadoMenu::Borrador);
});

test('el equipo técnico de nutrición puede aprobar el menú', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    app(InstitucionContext::class)->set($institucion->id);
    $nutricionista = usuarioNutricion($institucion);
    $menu = MenuSemanal::factory()->create();

    Livewire::actingAs($nutricionista)
        ->test(MenuEditor::class, ['menu' => $menu->id])
        ->call('aprobar');

    $menu->refresh();
    expect($menu->estado)->toBe(EstadoMenu::Aprobado)
        ->and($menu->aprobado_por_id)->toBe($nutricionista->id)
        ->and($menu->aprobado_en)->not->toBeNull();
});

test('un menú aprobado ya no se puede editar', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $menu = MenuSemanal::factory()->create(['estado' => EstadoMenu::Aprobado]);
    $item = ItemDeMenu::factory()->for($menu, 'menuSemanal')->create(['dia' => DiaSemana::Lunes, 'comida' => MomentoComida::Desayuno, 'descripcion' => 'Original']);

    Livewire::actingAs($encargado)
        ->test(MenuEditor::class, ['menu' => $menu->id])
        ->assertSet('soloLectura', true)
        ->set('descripciones.lunes|desayuno', 'Cambiado')
        ->call('guardar');

    expect($item->fresh()->descripcion)->toBe('Original');
});

test('el historial muestra menús de semanas anteriores', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    MenuSemanal::factory()->create(['semana_inicio' => Carbon::today()->subWeeks(3)->startOfWeek(), 'estado' => EstadoMenu::Aprobado]);

    Livewire::actingAs($encargado)
        ->test(Menus::class)
        ->assertSee(Carbon::today()->subWeeks(3)->startOfWeek()->format('d/m/Y'))
        ->assertSee('aprobado');
});

test('un menú de otra institución no aparece en el historial', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    MenuSemanal::factory()->create(['semana_inicio' => Carbon::today()->subWeek()->startOfWeek()]);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(Menus::class)
        ->assertDontSee(Carbon::today()->subWeek()->startOfWeek()->format('d/m/Y'));
});
