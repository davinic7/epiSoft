<?php

use App\Enums\DiaSemana;
use App\Enums\MomentoComida;
use App\Enums\RolInstitucional;
use App\Livewire\Economato\MenuEditor;
use App\Livewire\Economato\RestriccionesPorSala;
use App\Models\Alergia;
use App\Models\Institucion;
use App\Models\ItemDeMenu;
use App\Models\MenuSemanal;
use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder al listado de restricciones por sala', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.restricciones-por-sala.index'))
        ->assertForbidden();
});

test('el personal de cocina puede ver las restricciones por sala', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $institucion);
    $cocina->instituciones()->attach($institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create(['nombre' => 'Sala de bebés']);
    $nino = Nino::factory()->create(['nombres' => 'Juana', 'apellidos' => 'Pérez', 'sala_id' => $sala->id]);
    Alergia::factory()->for($nino)->create(['descripcion' => 'Maní']);
    $this->flushSession();

    $this->actingAs($cocina)
        ->get(route('economato.restricciones-por-sala.index'))
        ->assertOk()
        ->assertSee('Sala de bebés')
        ->assertSee('Juana')
        ->assertSee('Maní');
});

test('una sala sin niños con alergias no aparece en el listado', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $sala = Sala::factory()->create(['nombre' => 'Sala sin alergias']);
    Nino::factory()->create(['sala_id' => $sala->id]);

    Livewire::actingAs($cocina)
        ->test(RestriccionesPorSala::class)
        ->assertDontSee('Sala sin alergias');
});

test('un niño de otra institución no aparece en el listado de restricciones', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    $salaAjena = Sala::factory()->create(['nombre' => 'Sala de EPI B']);
    $ninoAjeno = Nino::factory()->create(['sala_id' => $salaAjena->id]);
    Alergia::factory()->for($ninoAjeno)->create(['descripcion' => 'Maní']);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($cocina)
        ->test(RestriccionesPorSala::class)
        ->assertDontSee('Sala de EPI B');
});

test('el editor de menú advierte cuando una casilla menciona una alergia cargada', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create(['nombres' => 'Juana', 'apellidos' => 'Pérez']);
    Alergia::factory()->for($nino)->create(['descripcion' => 'Maní']);
    $menu = MenuSemanal::factory()->create();
    ItemDeMenu::factory()->for($menu, 'menuSemanal')->create([
        'dia' => DiaSemana::Lunes,
        'comida' => MomentoComida::Almuerzo,
        'descripcion' => 'Guiso con salsa de maní',
    ]);

    Livewire::actingAs($encargado)
        ->test(MenuEditor::class, ['menu' => $menu->id])
        ->assertSee('Juana Pérez')
        ->assertSee('Maní');
});

test('el editor de menú no advierte cuando ninguna casilla menciona una alergia', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create(['nombres' => 'Juana', 'apellidos' => 'Pérez']);
    Alergia::factory()->for($nino)->create(['descripcion' => 'Maní']);
    $menu = MenuSemanal::factory()->create();
    ItemDeMenu::factory()->for($menu, 'menuSemanal')->create([
        'dia' => DiaSemana::Lunes,
        'comida' => MomentoComida::Almuerzo,
        'descripcion' => 'Arroz con verduras',
    ]);

    Livewire::actingAs($encargado)
        ->test(MenuEditor::class, ['menu' => $menu->id])
        ->assertDontSee('Coincide con alergias');
});
