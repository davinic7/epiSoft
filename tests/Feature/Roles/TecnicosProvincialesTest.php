<?php

use App\Enums\EquipoTecnicoProvincial;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitucionContext;

it('no permite fijar equipo_tecnico_provincial por asignación masiva', function () {
    $usuario = new User;
    $usuario->fill(['equipo_tecnico_provincial' => EquipoTecnicoProvincial::Nutricion->value]);

    expect($usuario->equipo_tecnico_provincial)->toBeNull();
});

it('un técnico provincial ya existente queda matriculado automáticamente en una institución nueva', function () {
    $tecnica = User::factory()->create(['equipo_tecnico_provincial' => EquipoTecnicoProvincial::Nutricion]);

    $epi = Institucion::create(['nombre' => 'EPI A']);

    expect($epi->usuarios()->whereKey($tecnica->id)->exists())->toBeTrue();

    app(InstitucionContext::class)->set($epi->id);
    expect(User::find($tecnica->id)->hasRole(EquipoTecnicoProvincial::Nutricion->value))->toBeTrue();
});

it('un usuario sin equipo_tecnico_provincial no se matricula en las instituciones nuevas', function () {
    $usuario = User::factory()->create();

    $epi = Institucion::create(['nombre' => 'EPI A']);

    expect($epi->usuarios()->whereKey($usuario->id)->exists())->toBeFalse();
});

it('matricular técnicos al crear una institución no altera la institución activa de la sesión', function () {
    User::factory()->create(['equipo_tecnico_provincial' => EquipoTecnicoProvincial::TrabajoSocial]);

    $epiExistente = Institucion::create(['nombre' => 'EPI existente']);
    app(InstitucionContext::class)->set($epiExistente->id);

    Institucion::create(['nombre' => 'EPI nueva']);

    expect(app(InstitucionContext::class)->id())->toBe($epiExistente->id);
});

it('un técnico asignado a varias instituciones tiene el rol en cada una sin perderlo en las demás', function () {
    $tecnico = User::factory()->create(['equipo_tecnico_provincial' => EquipoTecnicoProvincial::AbordajeGlobal]);

    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);

    app(InstitucionContext::class)->set($epiA->id);
    expect(User::find($tecnico->id)->hasRole(EquipoTecnicoProvincial::AbordajeGlobal->value))->toBeTrue();

    app(InstitucionContext::class)->set($epiB->id);
    expect(User::find($tecnico->id)->hasRole(EquipoTecnicoProvincial::AbordajeGlobal->value))->toBeTrue();
});
