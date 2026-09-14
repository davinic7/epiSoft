<?php

use App\Enums\RolInstitucional;
use App\Models\Institucion;
use Spatie\Permission\Models\Role;

it('crear una institución siembra automáticamente sus 4 roles institucionales', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);

    $roles = Role::query()->where('institucion_id', $epi->id)->pluck('name')->sort()->values()->all();

    $esperados = collect(RolInstitucional::cases())->map(fn (RolInstitucional $rol) => $rol->value)->sort()->values()->all();

    expect($roles)->toBe($esperados);
});

it('cada institución tiene su propia copia de los roles, no comparte filas con otra', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);

    $rolDocenteEnA = Role::query()->where('institucion_id', $epiA->id)->where('name', 'docente')->firstOrFail();
    $rolDocenteEnB = Role::query()->where('institucion_id', $epiB->id)->where('name', 'docente')->firstOrFail();

    expect($rolDocenteEnA->id)->not->toBe($rolDocenteEnB->id);
});

it('la matriz de permisos por módulo y acción se aplica a cada rol', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);

    $direccion = Role::query()->where('institucion_id', $epi->id)->where('name', 'dirección')->firstOrFail();
    $docente = Role::query()->where('institucion_id', $epi->id)->where('name', 'docente')->firstOrFail();
    $nutricion = Role::query()->where('institucion_id', $epi->id)->where('name', 'nutrición')->firstOrFail();

    expect($direccion->hasPermissionTo('usuarios.eliminar'))->toBeTrue()
        ->and($direccion->hasPermissionTo('rrhh.eliminar'))->toBeTrue()
        ->and($docente->hasPermissionTo('ninos.editar'))->toBeTrue()
        ->and($docente->hasPermissionTo('usuarios.ver'))->toBeFalse()
        ->and($nutricion->hasPermissionTo('economato.crear'))->toBeTrue()
        ->and($nutricion->hasPermissionTo('ninos.editar'))->toBeFalse();
});
