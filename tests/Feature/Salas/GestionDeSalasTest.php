<?php

use App\Enums\RolInstitucional;
use App\Enums\Turno;
use App\Livewire\Salas\Index;
use App\Models\Institucion;
use App\Models\Sala;
use App\Models\User;
use App\Support\InstitucionContext;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder al listado de salas', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('salas.index'))
        ->assertForbidden();
});

test('el equipo de coordinación puede ver el listado de salas de su institución', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    $coordinador->instituciones()->attach($institucion);

    app(InstitucionContext::class)->set($institucion->id);
    Sala::factory()->create(['nombre' => 'Sala de bebés']);
    $this->flushSession();

    $this->actingAs($coordinador)
        ->get(route('salas.index'))
        ->assertOk()
        ->assertSee('Sala de bebés');
});

test('una sala de otra institución no aparece en el listado', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    Sala::factory()->create(['nombre' => 'Sala de EPI B']);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->assertDontSee('Sala de EPI B');
});

test('el equipo de coordinación puede crear una sala con turno y capacidad', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('nombre', 'Sala de 1 año')
        ->set('turno', Turno::Manana->value)
        ->set('capacidad', 12)
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Sala::where('nombre', 'Sala de 1 año')->exists())->toBeTrue();
});

test('crear una sala exige los campos obligatorios', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('nombre', '')
        ->set('turno', '')
        ->set('capacidad', null)
        ->call('guardar')
        ->assertHasErrors(['nombre', 'turno', 'capacidad']);
});

test('el nombre de una sala debe ser único por turno dentro de la institución', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Sala::factory()->create(['nombre' => 'Sala de bebés', 'turno' => Turno::Manana]);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('nombre', 'Sala de bebés')
        ->set('turno', Turno::Manana->value)
        ->set('capacidad', 10)
        ->call('guardar')
        ->assertHasErrors(['nombre']);
});

test('el mismo nombre de sala puede repetirse en otro turno', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Sala::factory()->create(['nombre' => 'Sala de bebés', 'turno' => Turno::Manana]);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('nombre', 'Sala de bebés')
        ->set('turno', Turno::Tarde->value)
        ->set('capacidad', 10)
        ->call('guardar')
        ->assertHasNoErrors();
});

test('el equipo de coordinación puede editar una sala existente', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create(['nombre' => 'Sala vieja', 'capacidad' => 10]);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->call('editar', $sala->id)
        ->assertSet('nombre', 'Sala vieja')
        ->set('capacidad', 15)
        ->call('guardar')
        ->assertHasNoErrors();

    expect($sala->fresh()->capacidad)->toBe(15);
});

test('el equipo de coordinación puede eliminar una sala', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->call('eliminar', $sala->id);

    expect(Sala::query()->find($sala->id))->toBeNull();
});

test('el superadmin accede a las salas de la institución activa', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    app(InstitucionContext::class)->set($institucion->id);
    $superadmin = User::factory()->create(['is_superadmin' => true]);

    $this->actingAs($superadmin)
        ->get(route('salas.index'))
        ->assertOk();
});
