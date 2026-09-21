<?php

use App\Enums\RolInstitucional;
use App\Livewire\Salas\Asistencia;
use App\Models\Asistencia as AsistenciaModelo;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Referente;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder a la toma de asistencia', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $sala = Sala::factory()->create();

    Livewire::actingAs($usuario)->test(Asistencia::class, ['sala' => $sala->id])->assertForbidden();
});

test('el encargado de recepción puede tomar asistencia aunque no edite el legajo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoRecepcion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);

    Livewire::actingAs($encargado)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->assertSet('soloLectura', false)
        ->set("filas.{$nino->id}.presente", true)
        ->set("filas.{$nino->id}.horaIngreso", '08:15')
        ->call('guardar')
        ->assertHasNoErrors();

    $registro = AsistenciaModelo::where('nino_id', $nino->id)->sole();
    expect($registro->presente)->toBeTrue()
        ->and($registro->hora_ingreso)->toBe('08:15')
        ->and($registro->sala_id)->toBe($sala->id);
});

test('el coordinador pedagógico no puede guardar asistencia', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinadorPedagogico = usuarioConRol(RolInstitucional::CoordinadorPedagogico, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);

    Livewire::actingAs($coordinadorPedagogico)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->assertSet('soloLectura', true)
        ->set("filas.{$nino->id}.presente", true)
        ->call('guardar')
        ->assertForbidden();

    expect(AsistenciaModelo::where('nino_id', $nino->id)->count())->toBe(0);
});

test('marcar a un niño ausente no guarda horarios ni retirado por', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);

    Livewire::actingAs($coordinador)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->set("filas.{$nino->id}.presente", false)
        ->call('guardar')
        ->assertHasNoErrors();

    $registro = AsistenciaModelo::where('nino_id', $nino->id)->sole();
    expect($registro->presente)->toBeFalse()
        ->and($registro->retirado_por_id)->toBeNull();
});

test('solo se puede elegir como retirado a un referente autorizado de ese niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);
    $referenteAjeno = Referente::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->set("filas.{$nino->id}.presente", true)
        ->set("filas.{$nino->id}.retiradoPorId", $referenteAjeno->id)
        ->call('guardar')
        ->assertHasErrors(["filas.{$nino->id}.retiradoPorId"]);

    expect(AsistenciaModelo::where('nino_id', $nino->id)->count())->toBe(0);
});

test('un referente autorizado del niño puede quedar registrado como quien lo retiró', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);
    $referente = Referente::factory()->create();
    $nino->referentes()->attach($referente->id, ['parentesco' => 'madre', 'autorizado_a_retirar' => true]);

    Livewire::actingAs($coordinador)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->set("filas.{$nino->id}.presente", true)
        ->set("filas.{$nino->id}.horaEgreso", '13:00')
        ->set("filas.{$nino->id}.retiradoPorId", $referente->id)
        ->call('guardar')
        ->assertHasNoErrors();

    $registro = AsistenciaModelo::where('nino_id', $nino->id)->sole();
    expect($registro->retirado_por_id)->toBe($referente->id)
        ->and($registro->hora_egreso)->toBe('13:00');
});

test('guardar dos veces el mismo día actualiza el registro en vez de duplicarlo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);

    Livewire::actingAs($coordinador)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->set("filas.{$nino->id}.presente", true)
        ->call('guardar');

    Livewire::actingAs($coordinador)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->set("filas.{$nino->id}.presente", false)
        ->call('guardar');

    expect(AsistenciaModelo::where('nino_id', $nino->id)->count())->toBe(1)
        ->and(AsistenciaModelo::where('nino_id', $nino->id)->sole()->presente)->toBeFalse();
});

test('cambiar la fecha carga la asistencia ya registrada ese día', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);
    $ayer = Carbon::yesterday();
    AsistenciaModelo::factory()->for($nino)->create([
        'sala_id' => $sala->id,
        'fecha' => $ayer,
        'presente' => false,
    ]);

    Livewire::actingAs($coordinador)
        ->test(Asistencia::class, ['sala' => $sala->id])
        ->set('fecha', $ayer->format('Y-m-d'))
        ->assertSet("filas.{$nino->id}.presente", false);
});
