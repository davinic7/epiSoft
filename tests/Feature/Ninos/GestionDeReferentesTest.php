<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Referentes;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Referente;
use App\Support\InstitucionContext;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

function datosReferente(array $sobrescribir = []): array
{
    return array_merge([
        'dni' => '30111222',
        'nombres' => 'Marta',
        'apellidos' => 'González',
        'telefono' => '3834000000',
        'domicilio' => 'Calle Falsa 123',
        'parentesco' => 'madre',
        'autorizadoARetirar' => true,
    ], $sobrescribir);
}

test('un usuario sin permiso no puede acceder a los referentes de un niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($usuario)->test(Referentes::class, ['nino' => $nino->id])->assertForbidden();
});

test('el equipo de coordinación puede vincular un referente nuevo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Referentes::class, ['nino' => $nino->id])
        ->set(datosReferente())
        ->call('agregar')
        ->assertHasNoErrors();

    $referente = Referente::where('dni', '30111222')->sole();
    $vinculo = $nino->referentes()->where('referente_id', $referente->id)->sole();

    expect($vinculo->pivot->parentesco)->toBe('madre')
        ->and((bool) $vinculo->pivot->autorizado_a_retirar)->toBeTrue();
});

test('el encargado de recepción puede gestionar referentes aunque no edite el legajo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoRecepcion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($encargado)
        ->test(Referentes::class, ['nino' => $nino->id])
        ->assertSet('soloLectura', false)
        ->set(datosReferente())
        ->call('agregar')
        ->assertHasNoErrors();

    expect(Referente::where('dni', '30111222')->exists())->toBeTrue();
});

test('el coordinador pedagógico solo consulta los referentes, no los gestiona', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinadorPedagogico = usuarioConRol(RolInstitucional::CoordinadorPedagogico, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinadorPedagogico)
        ->test(Referentes::class, ['nino' => $nino->id])
        ->assertSet('soloLectura', true)
        ->set(datosReferente())
        ->call('agregar')
        ->assertForbidden();

    expect(Referente::where('dni', '30111222')->exists())->toBeFalse();
});

test('vincular un referente por dni ya cargado reutiliza el registro en vez de duplicarlo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $hermanoMayor = Nino::factory()->create();
    $referenteExistente = Referente::factory()->create(['dni' => '30111222']);
    $hermanoMayor->referentes()->attach($referenteExistente->id, [
        'parentesco' => 'madre',
        'autorizado_a_retirar' => true,
    ]);

    $hermanoMenor = Nino::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Referentes::class, ['nino' => $hermanoMenor->id])
        ->set(datosReferente(['dni' => '30111222']))
        ->call('agregar')
        ->assertHasNoErrors();

    expect(Referente::where('dni', '30111222')->count())->toBe(1)
        ->and($hermanoMenor->referentes()->where('referente_id', $referenteExistente->id)->exists())->toBeTrue()
        ->and($hermanoMayor->referentes()->where('referente_id', $referenteExistente->id)->exists())->toBeTrue();
});

test('no permite vincular dos veces el mismo referente a un niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create();
    $referente = Referente::factory()->create(['dni' => '30111222']);
    $nino->referentes()->attach($referente->id, ['parentesco' => 'madre', 'autorizado_a_retirar' => true]);

    Livewire::actingAs($coordinador)
        ->test(Referentes::class, ['nino' => $nino->id])
        ->set(datosReferente(['dni' => '30111222']))
        ->call('agregar')
        ->assertHasErrors(['dni']);

    expect($nino->referentes()->count())->toBe(1);
});

test('el equipo de coordinación puede editar el vínculo de un referente', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create();
    $referente = Referente::factory()->create();
    $nino->referentes()->attach($referente->id, ['parentesco' => 'tía', 'autorizado_a_retirar' => false]);

    Livewire::actingAs($coordinador)
        ->test(Referentes::class, ['nino' => $nino->id])
        ->call('editarVinculo', $referente->id)
        ->assertSet('parentescoEdicion', 'tía')
        ->assertSet('autorizadoEdicion', false)
        ->set('parentescoEdicion', 'madre')
        ->set('autorizadoEdicion', true)
        ->call('guardarVinculo')
        ->assertHasNoErrors();

    $vinculo = $nino->referentes()->where('referente_id', $referente->id)->sole();
    expect($vinculo->pivot->parentesco)->toBe('madre')
        ->and((bool) $vinculo->pivot->autorizado_a_retirar)->toBeTrue();
});

test('quitar un referente no borra su registro si sigue vinculado a otro niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $hermanoMayor = Nino::factory()->create();
    $hermanoMenor = Nino::factory()->create();
    $referente = Referente::factory()->create();
    $hermanoMayor->referentes()->attach($referente->id, ['parentesco' => 'madre', 'autorizado_a_retirar' => true]);
    $hermanoMenor->referentes()->attach($referente->id, ['parentesco' => 'madre', 'autorizado_a_retirar' => true]);

    Livewire::actingAs($coordinador)
        ->test(Referentes::class, ['nino' => $hermanoMenor->id])
        ->call('quitar', $referente->id);

    expect($hermanoMenor->referentes()->where('referente_id', $referente->id)->exists())->toBeFalse()
        ->and($hermanoMayor->referentes()->where('referente_id', $referente->id)->exists())->toBeTrue()
        ->and(Referente::whereKey($referente->id)->exists())->toBeTrue();
});

test('abrir la página de referentes registra un acceso en la auditoría del niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)->test(Referentes::class, ['nino' => $nino->id]);

    $registro = Audit::query()
        ->where('event', 'acceso')
        ->where('auditable_type', Nino::class)
        ->where('auditable_id', $nino->id)
        ->sole();

    expect($registro->user_id)->toBe($coordinador->id);
});
