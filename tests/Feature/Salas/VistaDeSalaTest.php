<?php

use App\Enums\RolInstitucional;
use App\Livewire\Salas\Ver;
use App\Models\Alergia;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Livewire\Livewire;

test('un usuario sin permiso no puede ver la nómina de una sala', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $sala = Sala::factory()->create();

    Livewire::actingAs($usuario)->test(Ver::class, ['sala' => $sala->id])->assertForbidden();
});

test('la vista de sala muestra solo a los niños asignados con sus alergias', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $otraSala = Sala::factory()->create();

    $ninoEnSala = Nino::factory()->create(['sala_id' => $sala->id, 'apellidos' => 'DeEstaSala']);
    Alergia::factory()->for($ninoEnSala)->create(['descripcion' => 'Maní', 'severidad' => 'grave']);

    Nino::factory()->create(['sala_id' => $otraSala->id, 'apellidos' => 'DeOtraSala']);
    Nino::factory()->create(['sala_id' => null, 'apellidos' => 'SinSala']);

    Livewire::actingAs($coordinador)
        ->test(Ver::class, ['sala' => $sala->id])
        ->assertSee('DeEstaSala')
        ->assertSee('Maní')
        ->assertDontSee('DeOtraSala')
        ->assertDontSee('SinSala');
});

test('una sala de otra institución no es accesible', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $epiA);
    $coordinador->instituciones()->attach($epiA);

    app(InstitucionContext::class)->set($epiB->id);
    $salaAjena = Sala::factory()->create();
    $this->flushSession();

    $this->actingAs($coordinador)
        ->get(route('salas.ver', $salaAjena))
        ->assertNotFound();
});
