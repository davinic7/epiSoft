<?php

use App\Models\Institucion;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

it('el seeder principal deja roles asignables en la institución de prueba', function () {
    $this->seed(DatabaseSeeder::class);

    $institucion = Institucion::query()->where('nombre', 'EPI de prueba')->firstOrFail();

    expect(Role::query()->where('institucion_id', $institucion->id)->exists())->toBeTrue();
});
