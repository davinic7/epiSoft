<?php

use App\Enums\RolInstitucional;
use App\Livewire\Instituciones\Selector;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitucionContext;
use Livewire\Livewire;

function matricular(User $usuario, Institucion ...$instituciones): void
{
    foreach ($instituciones as $institucion) {
        $usuario->instituciones()->attach($institucion);
    }
}

it('fija automáticamente la única institución del usuario', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);
    Institucion::create(['nombre' => 'EPI B']);
    $usuario = User::factory()->create();
    matricular($usuario, $epi);

    $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

    expect(session('institucion_id'))->toBe($epi->id);
});

it('con varias instituciones y ninguna elegida no fija ninguna (fail-closed)', function () {
    $usuario = User::factory()->create();
    matricular($usuario, Institucion::create(['nombre' => 'EPI A']), Institucion::create(['nombre' => 'EPI B']));

    $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

    expect(session('institucion_id'))->toBeNull();
});

it('conserva la institución elegida mientras siga siendo válida', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $usuario = User::factory()->create();
    matricular($usuario, $epiA, $epiB);

    $this->actingAs($usuario)->withSession(['institucion_id' => $epiB->id])->get(route('dashboard'));

    expect(session('institucion_id'))->toBe($epiB->id);
});

it('descarta una institución en sesión a la que el usuario no pertenece', function () {
    $propia = Institucion::create(['nombre' => 'EPI A']);
    $ajena = Institucion::create(['nombre' => 'EPI B']);
    $usuario = User::factory()->create();
    matricular($usuario, $propia);

    $this->actingAs($usuario)->withSession(['institucion_id' => $ajena->id])->get(route('dashboard'));

    expect(session('institucion_id'))->toBe($propia->id);
});

it('el superadmin puede usar cualquier institución y solo se autoselecciona si hay una', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    $epiA = Institucion::create(['nombre' => 'EPI A']);

    $this->actingAs($superadmin)->get(route('dashboard'));
    expect(session('institucion_id'))->toBe($epiA->id);

    Institucion::create(['nombre' => 'EPI B']);
    $this->flushSession();

    $this->actingAs($superadmin)->get(route('dashboard'));
    expect(session('institucion_id'))->toBeNull();
});

it('el selector cambia a una institución propia y rechaza una ajena', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $ajena = Institucion::create(['nombre' => 'EPI C']);
    $usuario = User::factory()->create();
    matricular($usuario, $epiA, $epiB);

    Livewire::actingAs($usuario)
        ->test(Selector::class)
        ->call('cambiar', $epiB->id)
        ->assertOk();

    expect(app(InstitucionContext::class)->id())->toBe($epiB->id);

    Livewire::actingAs($usuario)
        ->test(Selector::class)
        ->call('cambiar', $ajena->id)
        ->assertForbidden();
});

it('un usuario matriculado en una sola institución ve la auditoría de esa institución sin elegirla', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = User::factory()->create();
    matricular($coordinador, $epi);
    app(InstitucionContext::class)->set($epi->id);
    $coordinador->assignRole(RolInstitucional::EquipoCoordinacion->value);
    $this->flushSession();

    $this->actingAs($coordinador)
        ->get(route('auditoria.index'))
        ->assertOk();
});
