<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Alergias;
use App\Livewire\Ninos\Formulario;
use App\Models\Alergia;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Nino;
use App\Support\InstitucionContext;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

test('un usuario sin permiso no puede acceder a las alergias de un niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($usuario)->test(Alergias::class, ['nino' => $nino->id])->assertForbidden();
});

test('el equipo de coordinación puede registrar una alergia', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Alergias::class, ['nino' => $nino->id])
        ->set('tipo', 'alergia')
        ->set('severidad', 'grave')
        ->set('descripcion', 'Maní')
        ->call('agregar')
        ->assertHasNoErrors();

    $registro = Alergia::where('nino_id', $nino->id)->sole();
    expect($registro->descripcion)->toBe('Maní')
        ->and($registro->severidad->value)->toBe('grave');
});

test('el coordinador pedagógico solo consulta las alergias, no las gestiona', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinadorPedagogico = usuarioConRol(RolInstitucional::CoordinadorPedagogico, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinadorPedagogico)
        ->test(Alergias::class, ['nino' => $nino->id])
        ->assertSet('soloLectura', true)
        ->set('tipo', 'alergia')
        ->set('severidad', 'leve')
        ->set('descripcion', 'Maní')
        ->call('agregar')
        ->assertForbidden();

    expect(Alergia::where('nino_id', $nino->id)->count())->toBe(0);
});

test('quitar una alergia la elimina', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();
    $alergia = Alergia::factory()->for($nino)->create();

    Livewire::actingAs($coordinador)
        ->test(Alergias::class, ['nino' => $nino->id])
        ->call('quitar', $alergia->id);

    expect(Alergia::whereKey($alergia->id)->exists())->toBeFalse();
});

test('el legajo muestra un aviso destacado si el niño tiene alergias', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();
    Alergia::factory()->for($nino)->create(['descripcion' => 'Lactosa', 'severidad' => 'grave']);

    Livewire::actingAs($coordinador)
        ->test(Formulario::class, ['nino' => $nino->id])
        ->assertSee('Alergias y restricciones alimentarias')
        ->assertSee('Lactosa');
});

test('el legajo no muestra el aviso de alergias si no hay ninguna registrada', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Formulario::class, ['nino' => $nino->id])
        ->assertDontSee('Alergias y restricciones alimentarias');
});

test('abrir las alergias registra un acceso en la auditoría del niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)->test(Alergias::class, ['nino' => $nino->id]);

    $registro = Audit::query()
        ->where('event', 'acceso')
        ->where('auditable_type', Nino::class)
        ->where('auditable_id', $nino->id)
        ->sole();

    expect($registro->user_id)->toBe($coordinador->id);
});
