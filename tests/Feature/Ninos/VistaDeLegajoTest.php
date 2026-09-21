<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Legajo;
use App\Models\Alergia;
use App\Models\Asistencia;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Referente;
use App\Models\Sala;
use App\Models\VacunaAplicada;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

test('un usuario sin permiso no puede ver el legajo completo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($usuario)->test(Legajo::class, ['nino' => $nino->id])->assertForbidden();
});

test('el legajo muestra los datos personales, la sala y los referentes', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create(['nombre' => 'Sala de bebés']);
    $nino = Nino::factory()->create(['nombres' => 'Juana', 'apellidos' => 'Pérez', 'sala_id' => $sala->id]);
    $referente = Referente::factory()->create(['nombres' => 'Marta', 'apellidos' => 'González']);
    $nino->referentes()->attach($referente->id, ['parentesco' => 'madre', 'autorizado_a_retirar' => true]);

    Livewire::actingAs($coordinador)
        ->test(Legajo::class, ['nino' => $nino->id])
        ->assertSee('Juana')
        ->assertSee('Pérez')
        ->assertSee('Sala de bebés')
        ->assertSee('Marta')
        ->assertSee('madre');
});

test('el legajo muestra alergias y el estado del carnet de vacunación', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['fecha_nacimiento' => Carbon::today()->subMonths(3)]);
    Alergia::factory()->for($nino)->create(['descripcion' => 'Maní', 'severidad' => 'grave']);
    VacunaAplicada::factory()->for($nino)->create(['vacuna_clave' => 'pentavalente_1']);

    Livewire::actingAs($coordinador)
        ->test(Legajo::class, ['nino' => $nino->id])
        ->assertSee('Maní')
        ->assertSee('Aplicada')
        ->assertSee('Atrasada');
});

test('el legajo muestra la asistencia del mes en curso', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);
    Asistencia::factory()->for($nino)->create([
        'sala_id' => $sala->id,
        'fecha' => Carbon::today(),
        'presente' => true,
        'hora_ingreso' => '08:30',
    ]);

    Livewire::actingAs($coordinador)
        ->test(Legajo::class, ['nino' => $nino->id])
        ->assertSee('Presente')
        ->assertSee('08:30');
});

test('abrir el legajo completo registra un acceso en la auditoría', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)->test(Legajo::class, ['nino' => $nino->id]);

    $registro = Audit::query()
        ->where('event', 'acceso')
        ->where('auditable_type', Nino::class)
        ->where('auditable_id', $nino->id)
        ->sole();

    expect($registro->user_id)->toBe($coordinador->id);
});
