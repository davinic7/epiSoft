<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Index;
use App\Models\Institucion;
use App\Models\Nino;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso de eliminar no puede dar de baja a un niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $educador = usuarioConRol(RolInstitucional::Educador, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($educador)
        ->test(Index::class)
        ->call('iniciarBaja', $nino->id)
        ->assertForbidden();

    expect($nino->fresh()->trashed())->toBeFalse();
});

test('el equipo de coordinación puede dar de baja a un niño con motivo y fecha', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->call('iniciarBaja', $nino->id)
        ->set('motivoBaja', 'Mudanza a otra localidad')
        ->set('fechaBaja', Carbon::yesterday()->format('Y-m-d'))
        ->call('guardarBaja')
        ->assertHasNoErrors();

    $nino->refresh();
    expect($nino->trashed())->toBeTrue()
        ->and($nino->motivo_baja)->toBe('Mudanza a otra localidad')
        ->and($nino->fecha_baja->format('Y-m-d'))->toBe(Carbon::yesterday()->format('Y-m-d'));
});

test('el motivo de baja es obligatorio', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->call('iniciarBaja', $nino->id)
        ->set('motivoBaja', '')
        ->call('guardarBaja')
        ->assertHasErrors(['motivoBaja']);

    expect($nino->fresh()->trashed())->toBeFalse();
});

test('la fecha de baja no puede ser futura', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->call('iniciarBaja', $nino->id)
        ->set('motivoBaja', 'Motivo')
        ->set('fechaBaja', Carbon::tomorrow()->format('Y-m-d'))
        ->call('guardarBaja')
        ->assertHasErrors(['fechaBaja']);
});

test('un niño dado de baja no aparece en el listado activo por defecto', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create(['apellidos' => 'Egresado']);
    $nino->darDeBaja('Egreso', Carbon::today());

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->assertDontSee('Egresado');
});

test('el listado de bajas muestra el motivo y la fecha', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create(['apellidos' => 'Egresado']);
    $nino->darDeBaja('Cambio de institución', Carbon::today());

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('estado', 'baja')
        ->assertSee('Egresado')
        ->assertSee('Cambio de institución');
});

test('restaurar a un niño lo vuelve a mostrar como activo y limpia el motivo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create(['apellidos' => 'Egresado']);
    $nino->darDeBaja('Motivo temporal', Carbon::today());

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('estado', 'baja')
        ->call('restaurar', $nino->id)
        ->assertHasNoErrors();

    $nino->refresh();
    expect($nino->trashed())->toBeFalse()
        ->and($nino->motivo_baja)->toBeNull()
        ->and($nino->fecha_baja)->toBeNull();
});
