<?php

use App\Enums\EquipoTecnicoProvincial;
use App\Models\Institucion;
use Spatie\Permission\Models\Role;

it('crear una institución siembra automáticamente los 3 roles de equipos técnicos provinciales', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);

    $roles = Role::query()
        ->where('institucion_id', $epi->id)
        ->whereIn('name', collect(EquipoTecnicoProvincial::cases())->map->value)
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    $esperados = collect(EquipoTecnicoProvincial::cases())->map(fn (EquipoTecnicoProvincial $equipo) => $equipo->value)->sort()->values()->all();

    expect($roles)->toBe($esperados);
});

it('la matriz de permisos por módulo y acción se aplica a cada equipo técnico provincial', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);

    $abordajeGlobal = Role::query()->where('institucion_id', $epi->id)->where('name', 'equipo técnico de abordaje global')->firstOrFail();
    $nutricion = Role::query()->where('institucion_id', $epi->id)->where('name', 'equipo técnico de nutrición')->firstOrFail();
    $trabajoSocial = Role::query()->where('institucion_id', $epi->id)->where('name', 'equipo técnico de trabajo social')->firstOrFail();

    expect($abordajeGlobal->hasPermissionTo('vulneraciones.ver'))->toBeTrue()
        ->and($abordajeGlobal->hasPermissionTo('pedagogico.crear'))->toBeFalse()
        ->and($nutricion->hasPermissionTo('economato.crear'))->toBeTrue()
        ->and($nutricion->hasPermissionTo('vulneraciones.ver'))->toBeFalse()
        ->and($trabajoSocial->hasPermissionTo('vulneraciones.crear'))->toBeTrue()
        ->and($trabajoSocial->hasPermissionTo('institucional.crear'))->toBeTrue()
        ->and($trabajoSocial->hasPermissionTo('ninos.editar'))->toBeFalse();
});
