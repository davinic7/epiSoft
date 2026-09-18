<?php

use App\Enums\RolInstitucional;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitucionContext;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

it('un rol asignado en la EPI A no otorga sus permisos en la EPI B', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $usuario = User::factory()->create();

    // El rol "educador" trae, entre otros, el permiso ninos.editar (ver
    // ProvisionadorDeRolesInstitucionales::MATRIZ). Cada institución tiene
    // su propia fila de rol "educador" con ese permiso, sembrada al crearse.
    app(InstitucionContext::class)->set($epiA->id);
    $usuario->assignRole(RolInstitucional::Educador->value);

    app(InstitucionContext::class)->set($epiA->id);
    expect(User::find($usuario->id)->can('ninos.editar'))->toBeTrue();

    app(InstitucionContext::class)->set($epiB->id);
    expect(User::find($usuario->id)->can('ninos.editar'))->toBeFalse();
});

it('el rol de un usuario en una institución no aplica en otra', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $usuario = User::factory()->create();

    Role::create(['name' => 'educador']);

    app(InstitucionContext::class)->set($epiA->id);
    $usuario->assignRole('educador');

    app(InstitucionContext::class)->set($epiA->id);
    expect(User::find($usuario->id)->hasRole('educador'))->toBeTrue();

    app(InstitucionContext::class)->set($epiB->id);
    expect(User::find($usuario->id)->hasRole('educador'))->toBeFalse();

    app(InstitucionContext::class)->set(null);
    expect(User::find($usuario->id)->hasRole('educador'))->toBeFalse();
});

it('un usuario puede tener un rol distinto en cada institución', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $usuario = User::factory()->create();

    Role::create(['name' => 'educador']);
    Role::create(['name' => 'equipo de coordinación']);

    app(InstitucionContext::class)->set($epiA->id);
    $usuario->assignRole('educador');

    app(InstitucionContext::class)->set($epiB->id);
    $usuario->assignRole('equipo de coordinación');

    app(InstitucionContext::class)->set($epiA->id);
    expect(User::find($usuario->id)->hasRole('educador'))->toBeTrue();
    expect(User::find($usuario->id)->hasRole('equipo de coordinación'))->toBeFalse();

    app(InstitucionContext::class)->set($epiB->id);
    expect(User::find($usuario->id)->hasRole('equipo de coordinación'))->toBeTrue();
    expect(User::find($usuario->id)->hasRole('educador'))->toBeFalse();
});

it('el superadmin tiene acceso sin importar la institución activa, incluso sin ninguna seleccionada', function () {
    $usuario = User::factory()->create(['is_superadmin' => true]);

    app(InstitucionContext::class)->set(null);
    expect(Gate::forUser($usuario)->allows('una-habilidad-inventada'))->toBeTrue();

    $epi = Institucion::create(['nombre' => 'EPI A']);
    app(InstitucionContext::class)->set($epi->id);
    expect(Gate::forUser($usuario)->allows('otra-habilidad-inventada'))->toBeTrue();
});

it('un usuario normal no recibe el bypass del superadmin', function () {
    $usuario = User::factory()->create(['is_superadmin' => false]);

    expect(Gate::forUser($usuario)->allows('una-habilidad-inventada'))->toBeFalse();
});
