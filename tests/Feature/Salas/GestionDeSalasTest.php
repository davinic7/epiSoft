<?php

use App\Enums\RolInstitucional;
use App\Enums\Turno;
use App\Livewire\Salas\Index;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Sala;
use App\Models\User;
use App\Support\InstitucionContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

function usuarioDeSalasConRol(RolInstitucional $rol, Institucion $institucion): User
{
    $usuario = User::factory()->create();
    $usuario->instituciones()->attach($institucion);

    app(InstitucionContext::class)->set($institucion->id);
    $usuario->assignRole($rol->value);

    return $usuario;
}

test('un visitante sin sesión es redirigido al login', function () {
    $this->get(route('salas.index'))->assertRedirect(route('login'));
});

test('un rol sin permisos sobre niños no puede ver las salas', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('salas.index'))
        ->assertForbidden();
});

test('un rol con permiso ve solo las salas de su institución activa', function () {
    $institucion = Institucion::factory()->create();
    $ajena = Institucion::factory()->create();
    Sala::factory()->for($institucion)->create(['nombre' => 'Sala Girasoles']);
    Sala::factory()->for($ajena)->create(['nombre' => 'Sala Ajena']);
    $usuario = usuarioDeSalasConRol(RolInstitucional::CoordinadorPedagogico, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('salas.index'))
        ->assertOk()
        ->assertSee('Sala Girasoles')
        ->assertDontSee('Sala Ajena');
});

test('la política de salas sigue los permisos de niños de cada rol', function (RolInstitucional $rol, array $permitido) {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $usuario = usuarioDeSalasConRol($rol, $institucion);

    expect($usuario->can('viewAny', Sala::class))->toBe($permitido['ver'])
        ->and($usuario->can('create', Sala::class))->toBe($permitido['crear'])
        ->and($usuario->can('update', $sala))->toBe($permitido['editar'])
        ->and($usuario->can('delete', $sala))->toBe($permitido['eliminar']);
})->with([
    'coordinación' => [RolInstitucional::EquipoCoordinacion, ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true]],
    'educador' => [RolInstitucional::Educador, ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false]],
    'coordinador pedagógico' => [RolInstitucional::CoordinadorPedagogico, ['ver' => true, 'crear' => false, 'editar' => false, 'eliminar' => false]],
    'mantenimiento' => [RolInstitucional::PersonalMantenimiento, ['ver' => false, 'crear' => false, 'editar' => false, 'eliminar' => false]],
]);

test('coordinación crea una sala en la institución activa', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->set('nombre', 'Sala Girasoles')
        ->set('turno', Turno::Manana->value)
        ->set('capacidad', 15)
        ->call('guardar')
        ->assertHasNoErrors();

    $sala = Sala::sole();

    expect($sala->institucion_id)->toBe($institucion->id)
        ->and($sala->nombre)->toBe('Sala Girasoles')
        ->and($sala->turno)->toBe(Turno::Manana)
        ->and($sala->capacidad)->toBe(15);
});

test('crear una sala exige los campos obligatorios', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->call('guardar')
        ->assertHasErrors(['nombre', 'turno', 'capacidad']);
});

test('el turno debe ser uno de los definidos', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->set('nombre', 'Sala Girasoles')
        ->set('turno', 'madrugada')
        ->set('capacidad', 15)
        ->call('guardar')
        ->assertHasErrors(['turno']);
});

test('la capacidad debe ser al menos uno', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->set('nombre', 'Sala Girasoles')
        ->set('turno', Turno::Tarde->value)
        ->set('capacidad', 0)
        ->call('guardar')
        ->assertHasErrors(['capacidad']);
});

test('el nombre de una sala no se repite dentro de la institución pero sí entre instituciones', function () {
    $institucion = Institucion::factory()->create();
    $otra = Institucion::factory()->create();
    Sala::factory()->for($institucion)->create(['nombre' => 'Sala Girasoles']);
    Sala::factory()->for($otra)->create(['nombre' => 'Sala Lunas']);
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    $componente = Livewire::actingAs($usuario)
        ->test(Index::class)
        ->set('turno', Turno::Tarde->value)
        ->set('capacidad', 10);

    $componente->set('nombre', 'Sala Girasoles')->call('guardar')->assertHasErrors(['nombre']);
    $componente->set('nombre', 'Sala Lunas')->call('guardar')->assertHasNoErrors();
});

test('se puede editar una sala conservando su propio nombre', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create(['nombre' => 'Sala Girasoles', 'capacidad' => 10]);
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->call('editar', $sala->id)
        ->assertSet('nombre', 'Sala Girasoles')
        ->set('capacidad', 12)
        ->call('guardar')
        ->assertHasNoErrors();

    expect($sala->fresh()->capacidad)->toBe(12);
});

test('una sala de otra institución no se puede editar ni eliminar', function () {
    $institucion = Institucion::factory()->create();
    $ajena = Sala::factory()->for(Institucion::factory())->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    $componente = Livewire::actingAs($usuario)->test(Index::class);

    expect(fn () => $componente->call('editar', $ajena->id)->call('eliminar', $ajena->id))
        ->toThrow(ModelNotFoundException::class);
    expect(Sala::withoutGlobalScopes()->find($ajena->id))->not->toBeNull();
});

test('coordinación elimina una sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)->test(Index::class)->call('eliminar', $sala->id);

    expect(Sala::find($sala->id))->toBeNull();
});

test('un educador no puede eliminar una sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::Educador, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->call('eliminar', $sala->id)
        ->assertForbidden();

    expect(Sala::find($sala->id))->not->toBeNull();
});

test('el alta de una sala queda en la auditoría de su institución', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeSalasConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->set('nombre', 'Sala Girasoles')
        ->set('turno', Turno::Manana->value)
        ->set('capacidad', 15)
        ->call('guardar');

    $registro = Audit::withoutGlobalScopes()->where('auditable_type', Sala::class)->sole();

    expect($registro->event)->toBe('created')
        ->and($registro->institucion_id)->toBe($institucion->id)
        ->and($registro->user_id)->toBe($usuario->id);
});
