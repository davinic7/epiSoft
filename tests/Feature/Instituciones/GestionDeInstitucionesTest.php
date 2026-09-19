<?php

use App\Livewire\Instituciones\Index;
use App\Models\Institucion;
use App\Models\User;
use Livewire\Livewire;

test('un usuario sin rol de superadmin no puede acceder al listado de instituciones', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->get(route('instituciones.index'))
        ->assertForbidden();
});

test('el superadmin puede ver el listado de instituciones', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    $institucion = Institucion::factory()->create(['nombre' => 'EPI Demo']);

    $this->actingAs($superadmin)
        ->get(route('instituciones.index'))
        ->assertOk()
        ->assertSee('EPI Demo');
});

test('el superadmin puede crear una institución', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);

    Livewire::actingAs($superadmin)
        ->test(Index::class)
        ->set('nombre', 'EPI Nueva')
        ->set('direccion', 'Calle Falsa 123')
        ->set('cuit', '20-12345678-9')
        ->set('referente', 'Ana Referente')
        ->set('capacidad', 40)
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Institucion::where('nombre', 'EPI Nueva')->exists())->toBeTrue();
});

test('crear una institución exige los campos obligatorios', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);

    Livewire::actingAs($superadmin)
        ->test(Index::class)
        ->set('nombre', '')
        ->set('direccion', '')
        ->set('cuit', '')
        ->set('referente', '')
        ->set('capacidad', null)
        ->call('guardar')
        ->assertHasErrors(['nombre', 'direccion', 'cuit', 'referente', 'capacidad']);
});

test('el CUIT de una institución debe ser único', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    Institucion::factory()->create(['cuit' => '20-12345678-9']);

    Livewire::actingAs($superadmin)
        ->test(Index::class)
        ->set('nombre', 'EPI Nueva')
        ->set('direccion', 'Calle Falsa 123')
        ->set('cuit', '20-12345678-9')
        ->set('referente', 'Ana Referente')
        ->set('capacidad', 40)
        ->call('guardar')
        ->assertHasErrors(['cuit']);
});

test('el superadmin puede editar una institución existente', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    $institucion = Institucion::factory()->create(['nombre' => 'EPI Vieja']);

    Livewire::actingAs($superadmin)
        ->test(Index::class)
        ->call('editar', $institucion->id)
        ->assertSet('nombre', 'EPI Vieja')
        ->set('nombre', 'EPI Renombrada')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($institucion->fresh()->nombre)->toBe('EPI Renombrada');
});

test('el superadmin puede eliminar una institución', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    $institucion = Institucion::factory()->create();

    Livewire::actingAs($superadmin)
        ->test(Index::class)
        ->call('eliminar', $institucion->id);

    $this->assertSoftDeleted($institucion);
});
