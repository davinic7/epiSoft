<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Index;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\User;
use App\Support\InstitucionContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

function usuarioDeNinosConRol(RolInstitucional $rol, Institucion $institucion): User
{
    $usuario = User::factory()->create();
    $usuario->instituciones()->attach($institucion);

    app(InstitucionContext::class)->set($institucion->id);
    $usuario->assignRole($rol->value);

    return $usuario;
}

/**
 * @return array<string, mixed>
 */
function datosDeNino(array $cambios = []): array
{
    return [
        'apellido' => 'Pérez',
        'nombre' => 'Lucía',
        'dni' => '52123456',
        'fecha_nacimiento' => '2023-05-10',
        'domicilio' => 'Calle Falsa 123',
        ...$cambios,
    ];
}

function completarLegajo($componente, array $datos)
{
    foreach ($datos as $campo => $valor) {
        $componente->set($campo, $valor);
    }

    return $componente;
}

test('un visitante sin sesión es redirigido al login', function () {
    $this->get(route('ninos.index'))->assertRedirect(route('login'));
});

test('un rol sin permisos sobre niños no puede ver el listado', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('ninos.index'))
        ->assertForbidden();
});

test('el listado muestra solo los niños de la institución activa', function () {
    $institucion = Institucion::factory()->create();
    Nino::factory()->for($institucion)->create(['apellido' => 'Gómez']);
    Nino::factory()->for(Institucion::factory())->create(['apellido' => 'Ajeno']);
    $usuario = usuarioDeNinosConRol(RolInstitucional::CoordinadorPedagogico, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('ninos.index'))
        ->assertOk()
        ->assertSee('Gómez')
        ->assertDontSee('Ajeno');
});

test('el listado se pagina de a quince legajos', function () {
    $institucion = Institucion::factory()->create();
    Nino::factory()->for($institucion)->count(16)->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::CoordinadorPedagogico, $institucion);

    $listado = Livewire::actingAs($usuario)->test(Index::class)->instance()->ninos;

    expect($listado->count())->toBe(15)
        ->and($listado->total())->toBe(16);
});

test('la política de niños sigue los permisos de niños de cada rol', function (RolInstitucional $rol, array $permitido) {
    $institucion = Institucion::factory()->create();
    $nino = Nino::factory()->for($institucion)->create();
    $usuario = usuarioDeNinosConRol($rol, $institucion);

    expect($usuario->can('viewAny', Nino::class))->toBe($permitido['ver'])
        ->and($usuario->can('view', $nino))->toBe($permitido['ver'])
        ->and($usuario->can('create', Nino::class))->toBe($permitido['crear'])
        ->and($usuario->can('update', $nino))->toBe($permitido['editar'])
        ->and($usuario->can('delete', $nino))->toBe($permitido['eliminar']);
})->with([
    'coordinación' => [RolInstitucional::EquipoCoordinacion, ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true]],
    'educador' => [RolInstitucional::Educador, ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false]],
    'coordinador pedagógico' => [RolInstitucional::CoordinadorPedagogico, ['ver' => true, 'crear' => false, 'editar' => false, 'eliminar' => false]],
    'mantenimiento' => [RolInstitucional::PersonalMantenimiento, ['ver' => false, 'crear' => false, 'editar' => false, 'eliminar' => false]],
]);

test('coordinación crea un legajo en la institución activa', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    completarLegajo(Livewire::actingAs($usuario)->test(Index::class), datosDeNino())
        ->call('guardar')
        ->assertHasNoErrors();

    $nino = Nino::sole();

    expect($nino->institucion_id)->toBe($institucion->id)
        ->and($nino->nombreCompleto())->toBe('Pérez, Lucía')
        ->and($nino->dni)->toBe('52123456')
        ->and($nino->fecha_nacimiento->toDateString())->toBe('2023-05-10');
});

test('crear un legajo exige los campos obligatorios', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->call('guardar')
        ->assertHasErrors(['apellido', 'nombre', 'dni', 'fecha_nacimiento'])
        ->assertHasNoErrors(['domicilio']);
});

test('el DNI debe tener siete u ocho dígitos', function (string $dni) {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    completarLegajo(Livewire::actingAs($usuario)->test(Index::class), datosDeNino(['dni' => $dni]))
        ->call('guardar')
        ->assertHasErrors(['dni']);
})->with([
    'con letras' => ['12ab5678'],
    'muy corto' => ['123456'],
    'muy largo' => ['123456789'],
]);

test('la fecha de nacimiento no puede ser futura', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    completarLegajo(
        Livewire::actingAs($usuario)->test(Index::class),
        datosDeNino(['fecha_nacimiento' => now()->addDay()->toDateString()]),
    )
        ->call('guardar')
        ->assertHasErrors(['fecha_nacimiento']);
});

test('el DNI no se repite dentro de la institución pero sí entre instituciones', function () {
    $institucion = Institucion::factory()->create();
    Nino::factory()->for($institucion)->create(['dni' => '52123456']);
    Nino::factory()->for(Institucion::factory())->create(['dni' => '52999999']);
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    $componente = completarLegajo(Livewire::actingAs($usuario)->test(Index::class), datosDeNino());

    $componente->set('dni', '52123456')->call('guardar')->assertHasErrors(['dni']);
    $componente->set('dni', '52999999')->call('guardar')->assertHasNoErrors();
});

test('se puede editar un legajo conservando su propio DNI', function () {
    $institucion = Institucion::factory()->create();
    $nino = Nino::factory()->for($institucion)->create(['dni' => '52123456', 'domicilio' => 'Vieja 1']);
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->call('editar', $nino->id)
        ->assertSet('dni', '52123456')
        ->set('domicilio', 'Nueva 2')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($nino->fresh()->domicilio)->toBe('Nueva 2');
});

test('un legajo de otra institución no se puede editar ni eliminar', function () {
    $institucion = Institucion::factory()->create();
    $ajeno = Nino::factory()->for(Institucion::factory())->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    $componente = Livewire::actingAs($usuario)->test(Index::class);

    expect(fn () => $componente->call('editar', $ajeno->id))->toThrow(ModelNotFoundException::class);
    expect(fn () => $componente->call('eliminar', $ajeno->id))->toThrow(ModelNotFoundException::class);
    expect(Nino::withoutGlobalScopes()->find($ajeno->id))->not->toBeNull();
});

test('coordinación elimina un legajo', function () {
    $institucion = Institucion::factory()->create();
    $nino = Nino::factory()->for($institucion)->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)->test(Index::class)->call('eliminar', $nino->id);

    expect(Nino::find($nino->id))->toBeNull();
});

test('un educador no puede eliminar un legajo', function () {
    $institucion = Institucion::factory()->create();
    $nino = Nino::factory()->for($institucion)->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::Educador, $institucion);

    Livewire::actingAs($usuario)
        ->test(Index::class)
        ->call('eliminar', $nino->id)
        ->assertForbidden();

    expect(Nino::find($nino->id))->not->toBeNull();
});

test('abrir un legajo muestra sus datos y deja constancia del acceso en la auditoría', function () {
    $institucion = Institucion::factory()->create();
    $nino = Nino::factory()->for($institucion)->create(['apellido' => 'Gómez', 'domicilio' => 'Calle Sol 45']);
    $usuario = usuarioDeNinosConRol(RolInstitucional::CoordinadorPedagogico, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('ninos.show', $nino))
        ->assertOk()
        ->assertSee('Gómez')
        ->assertSee('Calle Sol 45');

    $registro = Audit::withoutGlobalScopes()
        ->where('auditable_type', Nino::class)
        ->where('event', 'acceso')
        ->sole();

    expect($registro->auditable_id)->toBe($nino->id)
        ->and($registro->user_id)->toBe($usuario->id)
        ->and($registro->institucion_id)->toBe($institucion->id);
});

test('abrir el legajo de otra institución responde 404 y no deja constancia', function () {
    $institucion = Institucion::factory()->create();
    $ajeno = Nino::factory()->for(Institucion::factory())->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('ninos.show', $ajeno))
        ->assertNotFound();

    expect(Audit::withoutGlobalScopes()->where('event', 'acceso')->exists())->toBeFalse();
});

test('un rol sin permiso sobre niños no puede abrir un legajo ni deja constancia', function () {
    $institucion = Institucion::factory()->create();
    $nino = Nino::factory()->for($institucion)->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('ninos.show', $nino))
        ->assertForbidden();

    expect(Audit::withoutGlobalScopes()->where('event', 'acceso')->exists())->toBeFalse();
});

test('el alta de un legajo queda en la auditoría de su institución', function () {
    $institucion = Institucion::factory()->create();
    $usuario = usuarioDeNinosConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    completarLegajo(Livewire::actingAs($usuario)->test(Index::class), datosDeNino())->call('guardar');

    $registro = Audit::withoutGlobalScopes()
        ->where('auditable_type', Nino::class)
        ->where('event', 'created')
        ->sole();

    expect($registro->institucion_id)->toBe($institucion->id)
        ->and($registro->user_id)->toBe($usuario->id);
});
