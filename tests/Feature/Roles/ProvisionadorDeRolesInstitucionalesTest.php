<?php

use App\Enums\RolInstitucional;
use App\Models\Institucion;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('crear una institución siembra automáticamente sus 7 roles institucionales', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);

    $roles = Role::query()
        ->where('institucion_id', $epi->id)
        ->whereIn('name', collect(RolInstitucional::cases())->map->value)
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    $esperados = collect(RolInstitucional::cases())->map(fn (RolInstitucional $rol) => $rol->value)->sort()->values()->all();

    expect($roles)->toBe($esperados);
});

it('cada institución tiene su propia copia de los roles, no comparte filas con otra', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);

    $rolEducadorEnA = Role::query()->where('institucion_id', $epiA->id)->where('name', 'educador')->firstOrFail();
    $rolEducadorEnB = Role::query()->where('institucion_id', $epiB->id)->where('name', 'educador')->firstOrFail();

    expect($rolEducadorEnA->id)->not->toBe($rolEducadorEnB->id);
});

it('la matriz de permisos por módulo y acción se aplica a cada rol', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);

    $coordinacion = Role::query()->where('institucion_id', $epi->id)->where('name', 'equipo de coordinación')->firstOrFail();
    $educador = Role::query()->where('institucion_id', $epi->id)->where('name', 'educador')->firstOrFail();
    $economato = Role::query()->where('institucion_id', $epi->id)->where('name', 'encargado de economato')->firstOrFail();
    $mantenimiento = Role::query()->where('institucion_id', $epi->id)->where('name', 'personal de mantenimiento y limpieza')->firstOrFail();

    expect($coordinacion->hasPermissionTo('usuarios.eliminar'))->toBeTrue()
        ->and($coordinacion->hasPermissionTo('rrhh.eliminar'))->toBeTrue()
        ->and($coordinacion->hasPermissionTo('vulneraciones.ver'))->toBeTrue()
        ->and(Permission::query()->where('name', 'vulneraciones.eliminar')->exists())->toBeFalse()
        ->and($educador->hasPermissionTo('ninos.editar'))->toBeTrue()
        ->and($educador->hasPermissionTo('usuarios.ver'))->toBeFalse()
        ->and($educador->hasPermissionTo('vulneraciones.ver'))->toBeFalse()
        ->and($economato->hasPermissionTo('economato.crear'))->toBeTrue()
        ->and($economato->hasPermissionTo('ninos.editar'))->toBeFalse()
        ->and($mantenimiento->getAllPermissions())->toHaveCount(0);
});
