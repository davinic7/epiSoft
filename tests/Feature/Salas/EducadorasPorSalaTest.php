<?php

use App\Enums\RolInstitucional;
use App\Livewire\Salas\Show;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Sala;
use App\Models\User;
use App\Support\InstitucionContext;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

/**
 * Crea un usuario con el rol dado en la institución y deja esa institución
 * como activa: el último usuario creado define la institución del test.
 */
function personaDeSalaConRol(RolInstitucional $rol, Institucion $institucion, string $nombre = 'Persona'): User
{
    $usuario = User::factory()->create(['name' => $nombre]);
    $usuario->instituciones()->attach($institucion);

    app(InstitucionContext::class)->set($institucion->id);
    $usuario->assignRole($rol->value);

    return $usuario;
}

test('la sala muestra sus educadoras', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $sala->educadoras()->attach(personaDeSalaConRol(RolInstitucional::Educador, $institucion, 'Marta Educadora'));
    $usuario = personaDeSalaConRol(RolInstitucional::EncargadoRecepcion, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('salas.show', $sala))
        ->assertOk()
        ->assertSee('Marta Educadora');
});

test('una educadora que rotó a otra institución deja de aparecer en la sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $rotada = personaDeSalaConRol(RolInstitucional::Educador, $institucion, 'Rosa Rotada');
    $sala->educadoras()->attach($rotada);
    $rotada->instituciones()->detach($institucion);
    $usuario = personaDeSalaConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->assertDontSee('Rosa Rotada');
});

test('coordinación asigna varias educadoras a una sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $marta = personaDeSalaConRol(RolInstitucional::Educador, $institucion);
    $lucia = personaDeSalaConRol(RolInstitucional::Educador, $institucion);
    $usuario = personaDeSalaConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('educadorasElegidas', [(string) $marta->id, (string) $lucia->id])
        ->call('guardarEducadoras')
        ->assertDispatched('toast-show');

    expect($sala->educadoras()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$marta->id, $lucia->id])->sort()->values()->all());
});

test('cambiar las educadoras de una sala no toca las de otra sala', function () {
    $institucion = Institucion::factory()->create();
    $girasoles = Sala::factory()->for($institucion)->create();
    $tortuguitas = Sala::factory()->for($institucion)->create();
    $marta = personaDeSalaConRol(RolInstitucional::Educador, $institucion);
    $lucia = personaDeSalaConRol(RolInstitucional::Educador, $institucion);
    $girasoles->educadoras()->attach([$marta->id, $lucia->id]);
    $tortuguitas->educadoras()->attach($marta);
    $usuario = personaDeSalaConRol(RolInstitucional::CoordinadorPedagogico, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $girasoles])
        ->call('editarEducadoras')
        ->assertSet('educadorasElegidas', fn (array $elegidas) => count($elegidas) === 2)
        ->set('educadorasElegidas', [(string) $lucia->id])
        ->call('guardarEducadoras');

    expect($girasoles->educadoras()->pluck('users.id')->all())->toBe([$lucia->id])
        ->and($tortuguitas->educadoras()->pluck('users.id')->all())->toBe([$marta->id]);
});

test('solo se asignan personas con rol de educador en la institución', function () {
    $institucion = Institucion::factory()->create();
    $otra = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $deOtraInstitucion = personaDeSalaConRol(RolInstitucional::Educador, $otra);
    $cocinera = personaDeSalaConRol(RolInstitucional::PersonalCocina, $institucion);
    $educadora = personaDeSalaConRol(RolInstitucional::Educador, $institucion);
    $usuario = personaDeSalaConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('educadorasElegidas', [$deOtraInstitucion->id, $cocinera->id, $educadora->id])
        ->call('guardarEducadoras');

    expect($sala->educadoras()->pluck('users.id')->all())->toBe([$educadora->id]);
});

test('un educador no puede cambiar las educadoras de una sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $usuario = personaDeSalaConRol(RolInstitucional::Educador, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('educadorasElegidas', [$usuario->id])
        ->call('guardarEducadoras')
        ->assertForbidden();

    expect($sala->educadoras()->count())->toBe(0);
});

test('el cambio de educadoras queda en la auditoría de la institución', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $educadora = personaDeSalaConRol(RolInstitucional::Educador, $institucion, 'Marta Educadora');
    $usuario = personaDeSalaConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('educadorasElegidas', [$educadora->id])
        ->call('guardarEducadoras');

    $registro = Audit::withoutGlobalScopes()
        ->where('auditable_type', Sala::class)
        ->where('event', 'sync')
        ->sole();

    expect($registro->institucion_id)->toBe($institucion->id)
        ->and($registro->user_id)->toBe($usuario->id)
        ->and(json_encode($registro->new_values))->toContain('Marta Educadora');
});
