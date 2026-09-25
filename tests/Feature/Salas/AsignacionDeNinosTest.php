<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Index as NinosIndex;
use App\Livewire\Salas\Show;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Sala;
use App\Models\User;
use App\Support\InstitucionContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

function usuarioDeAsignacionConRol(RolInstitucional $rol, Institucion $institucion): User
{
    $usuario = User::factory()->create();
    $usuario->instituciones()->attach($institucion);

    app(InstitucionContext::class)->set($institucion->id);
    $usuario->assignRole($rol->value);

    return $usuario;
}

/**
 * Verifica el toast que muestra Flux después de una acción.
 */
function seMostroToast(string $variante, string $texto): Closure
{
    return fn (string $evento, array $parametros): bool => $parametros['dataset']['variant'] === $variante
        && str_contains($parametros['slots']['text'], $texto);
}

test('un rol sin permisos sobre salas no puede abrir una sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('salas.show', $sala))
        ->assertForbidden();
});

test('una sala de otra institución no se encuentra', function () {
    $institucion = Institucion::factory()->create();
    $ajena = Sala::factory()->for(Institucion::factory())->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('salas.show', $ajena))
        ->assertNotFound();
});

test('la sala muestra sus niños con la edad de cada uno y el rango de edades', function () {
    $this->travelTo('2026-09-25');
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $otra = Sala::factory()->for($institucion)->create();
    Nino::factory()->for($institucion)->for($sala)->create(['apellido' => 'Pérez', 'fecha_nacimiento' => '2026-01-10']);
    Nino::factory()->for($institucion)->for($sala)->create(['apellido' => 'Gómez', 'fecha_nacimiento' => '2025-06-20']);
    Nino::factory()->for($institucion)->for($otra)->create(['apellido' => 'Ajeno']);
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::Educador, $institucion);

    $this->actingAs($usuario)
        ->withSession(['institucion_id' => $institucion->id])
        ->get(route('salas.show', $sala))
        ->assertOk()
        ->assertSee('Pérez')
        ->assertSee('Gómez')
        ->assertDontSee('Ajeno')
        ->assertSee('Edades: de 8 meses a 1 año y 3 meses');
});

test('el coordinador pedagógico agrega a la sala niños que no tienen sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create(['capacidad' => 10]);
    $otra = Sala::factory()->for($institucion)->create();
    [$primero, $segundo] = Nino::factory()->for($institucion)->count(2)->create();
    $conSala = Nino::factory()->for($institucion)->for($otra)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::CoordinadorPedagogico, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('paraAgregar', [(string) $primero->id, (string) $segundo->id, (string) $conSala->id])
        ->call('agregar')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', seMostroToast('success', 'Niños agregados a la sala.'));

    expect($primero->fresh()->sala_id)->toBe($sala->id)
        ->and($segundo->fresh()->sala_id)->toBe($sala->id)
        ->and($conSala->fresh()->sala_id)->toBe($otra->id);
});

test('no se puede agregar a un niño de otra institución', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $ajeno = Nino::factory()->for(Institucion::factory())->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('paraAgregar', [$ajeno->id])
        ->call('agregar');

    expect(Nino::withoutGlobalScopes()->find($ajeno->id)->sala_id)->toBeNull();
});

test('agregar exige marcar al menos un niño', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->call('agregar')
        ->assertHasErrors(['paraAgregar' => 'Marcá al menos un niño para agregar.']);
});

test('superar el cupo se avisa pero no se impide', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create(['nombre' => 'Las Tortuguitas', 'capacidad' => 1]);
    $ninos = Nino::factory()->for($institucion)->count(2)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('paraAgregar', $ninos->modelKeys())
        ->call('agregar')
        ->assertDispatched('toast-show', seMostroToast('warning', 'Las Tortuguitas supera su cupo (2 niños para 1 lugares)'));

    expect($sala->ninos()->count())->toBe(2);
});

test('un educador puede ver la sala pero no asignar niños', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $nino = Nino::factory()->for($institucion)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::Educador, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('paraAgregar', [$nino->id])
        ->call('agregar')
        ->assertForbidden();

    expect($nino->fresh()->sala_id)->toBeNull();
});

test('el pase de sala en grupo mueve solo a los niños marcados de esta sala', function () {
    $institucion = Institucion::factory()->create();
    $origen = Sala::factory()->for($institucion)->create();
    $destino = Sala::factory()->for($institucion)->create(['capacidad' => 10]);
    $tercera = Sala::factory()->for($institucion)->create();
    [$movido, $queda] = Nino::factory()->for($institucion)->for($origen)->count(2)->create();
    $deOtraSala = Nino::factory()->for($institucion)->for($tercera)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $origen])
        ->set('seleccionados', [(string) $movido->id, (string) $deOtraSala->id])
        ->set('salaDestinoId', $destino->id)
        ->call('mover')
        ->assertHasNoErrors();

    expect($movido->fresh()->sala_id)->toBe($destino->id)
        ->and($queda->fresh()->sala_id)->toBe($origen->id)
        ->and($deOtraSala->fresh()->sala_id)->toBe($tercera->id);
});

test('mover exige niños marcados y una sala de destino', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->call('mover')
        ->assertHasErrors([
            'seleccionados' => 'Marcá al menos un niño para mover.',
            'salaDestinoId' => 'Elegí la sala a la que pasan.',
        ]);
});

test('no se puede mover niños a una sala de otra institución', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $ajena = Sala::factory()->for(Institucion::factory())->create();
    $nino = Nino::factory()->for($institucion)->for($sala)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    $componente = Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('seleccionados', [$nino->id])
        ->set('salaDestinoId', $ajena->id);

    expect(fn () => $componente->call('mover'))->toThrow(ModelNotFoundException::class);
    expect($nino->fresh()->sala_id)->toBe($sala->id);
});

test('quitar deja a los niños marcados sin sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    [$quitado, $queda] = Nino::factory()->for($institucion)->for($sala)->count(2)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::CoordinadorPedagogico, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('seleccionados', [$quitado->id])
        ->call('quitar')
        ->assertHasNoErrors();

    expect($quitado->fresh()->sala_id)->toBeNull()
        ->and($queda->fresh()->sala_id)->toBe($sala->id);
});

test('la asignación a una sala queda en la auditoría', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    $nino = Nino::factory()->for($institucion)->create();
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(Show::class, ['sala' => $sala])
        ->set('paraAgregar', [$nino->id])
        ->call('agregar');

    $registro = Audit::withoutGlobalScopes()
        ->where('auditable_type', Nino::class)
        ->where('event', 'updated')
        ->sole();

    expect($registro->new_values)->toMatchArray(['sala_id' => $sala->id])
        ->and($registro->user_id)->toBe($usuario->id);
});

test('el listado de niños filtra a los que no tienen sala', function () {
    $institucion = Institucion::factory()->create();
    $sala = Sala::factory()->for($institucion)->create();
    Nino::factory()->for($institucion)->create(['apellido' => 'Sinsala']);
    Nino::factory()->for($institucion)->for($sala)->create(['apellido' => 'Consala']);
    $usuario = usuarioDeAsignacionConRol(RolInstitucional::EquipoCoordinacion, $institucion);

    Livewire::actingAs($usuario)
        ->test(NinosIndex::class)
        ->set('filtroSala', 'sin-sala')
        ->assertSee('Sinsala')
        ->assertDontSee('Consala');
});
