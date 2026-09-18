<?php

use App\Models\Institucion;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('solo el equipo de coordinación y los equipos técnicos de abordaje global y trabajo social acceden a vulneraciones, y nadie puede eliminar', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);

    $rolesConAcceso = Role::query()
        ->where('institucion_id', $epi->id)
        ->get()
        ->filter(fn (Role $rol) => $rol->hasPermissionTo('vulneraciones.ver'))
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($rolesConAcceso)->toBe([
        'equipo de coordinación',
        'equipo técnico de abordaje global',
        'equipo técnico de trabajo social',
    ]);

    // El permiso "vulneraciones.eliminar" ni siquiera se siembra en el
    // catálogo: ninguna matriz (institucional ni provincial) lo incluye.
    expect(Permission::query()->where('name', 'vulneraciones.eliminar')->exists())->toBeFalse();
});
