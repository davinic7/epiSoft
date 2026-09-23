<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Vacunas;
use App\Livewire\Ninos\VacunasAtrasadas;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\VacunaAplicada;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

test('un usuario sin permiso no puede acceder al carnet de vacunación', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($usuario)->test(Vacunas::class, ['nino' => $nino->id])->assertForbidden();
});

test('una dosis cuya edad ya se alcanzó y no está aplicada figura atrasada', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['fecha_nacimiento' => Carbon::today()->subMonths(3)]);

    $componente = Livewire::actingAs($coordinador)->test(Vacunas::class, ['nino' => $nino->id]);

    $estados = $componente->instance()->estados()->keyBy('clave');

    expect($estados['pentavalente_1']['estado'])->toBe('atrasada')
        ->and($estados['pentavalente_2']['estado'])->toBe('pendiente');
});

test('el equipo de coordinación puede registrar una vacuna aplicada', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['fecha_nacimiento' => Carbon::today()->subMonths(3)]);

    Livewire::actingAs($coordinador)
        ->test(Vacunas::class, ['nino' => $nino->id])
        ->set('vacunaClave', 'pentavalente_1')
        ->set('fechaAplicacion', Carbon::today()->subMonth()->format('Y-m-d'))
        ->call('registrar')
        ->assertHasNoErrors();

    $registro = VacunaAplicada::where('nino_id', $nino->id)->where('vacuna_clave', 'pentavalente_1')->sole();
    expect($registro)->not->toBeNull();

    $estados = Livewire::actingAs($coordinador)
        ->test(Vacunas::class, ['nino' => $nino->id])
        ->instance()
        ->estados()
        ->keyBy('clave');

    expect($estados['pentavalente_1']['estado'])->toBe('aplicada');
});

test('no permite registrar dos veces la misma dosis', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['fecha_nacimiento' => Carbon::today()->subMonths(3)]);
    VacunaAplicada::factory()->for($nino)->create(['vacuna_clave' => 'pentavalente_1']);

    Livewire::actingAs($coordinador)
        ->test(Vacunas::class, ['nino' => $nino->id])
        ->set('vacunaClave', 'pentavalente_1')
        ->set('fechaAplicacion', Carbon::today()->format('Y-m-d'))
        ->call('registrar')
        ->assertHasErrors(['vacunaClave']);

    expect(VacunaAplicada::where('nino_id', $nino->id)->count())->toBe(1);
});

test('la fecha de aplicación no puede ser futura', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['fecha_nacimiento' => Carbon::today()->subMonths(3)]);

    Livewire::actingAs($coordinador)
        ->test(Vacunas::class, ['nino' => $nino->id])
        ->set('vacunaClave', 'pentavalente_1')
        ->set('fechaAplicacion', Carbon::tomorrow()->format('Y-m-d'))
        ->call('registrar')
        ->assertHasErrors(['fechaAplicacion']);
});

test('el encargado de recepción no puede registrar vacunas', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoRecepcion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['fecha_nacimiento' => Carbon::today()->subMonths(3)]);

    Livewire::actingAs($encargado)
        ->test(Vacunas::class, ['nino' => $nino->id])
        ->assertSet('soloLectura', true)
        ->set('vacunaClave', 'pentavalente_1')
        ->set('fechaAplicacion', Carbon::today()->format('Y-m-d'))
        ->call('registrar')
        ->assertForbidden();

    expect(VacunaAplicada::where('nino_id', $nino->id)->count())->toBe(0);
});

test('quitar un registro devuelve la dosis a atrasada si ya corresponde por edad', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['fecha_nacimiento' => Carbon::today()->subMonths(3)]);
    $registro = VacunaAplicada::factory()->for($nino)->create(['vacuna_clave' => 'pentavalente_1']);

    Livewire::actingAs($coordinador)
        ->test(Vacunas::class, ['nino' => $nino->id])
        ->call('quitar', $registro->id);

    expect(VacunaAplicada::whereKey($registro->id)->exists())->toBeFalse();

    $estados = Livewire::actingAs($coordinador)
        ->test(Vacunas::class, ['nino' => $nino->id])
        ->instance()
        ->estados()
        ->keyBy('clave');

    expect($estados['pentavalente_1']['estado'])->toBe('atrasada');
});

test('abrir el carnet registra un acceso en la auditoría del niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)->test(Vacunas::class, ['nino' => $nino->id]);

    $registro = Audit::query()
        ->where('event', 'acceso')
        ->where('auditable_type', Nino::class)
        ->where('auditable_id', $nino->id)
        ->sole();

    expect($registro->user_id)->toBe($coordinador->id);
});

test('el listado de vacunas atrasadas incluye solo a los niños con alguna dosis vencida', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $atrasado = Nino::factory()->create([
        'nombres' => 'Atrasado',
        'fecha_nacimiento' => Carbon::today()->subMonths(3),
    ]);
    $alDia = Nino::factory()->create([
        'nombres' => 'AlDia',
        'fecha_nacimiento' => Carbon::today()->subMonths(3),
    ]);
    foreach (['pentavalente_1', 'ipv_1', 'neumococo_1', 'rotavirus_1', 'bcg', 'hepatitis_b_rn'] as $clave) {
        VacunaAplicada::factory()->for($alDia)->create(['vacuna_clave' => $clave]);
    }
    $reciénNacido = Nino::factory()->create([
        'nombres' => 'ReciénNacido',
        'fecha_nacimiento' => Carbon::today(),
    ]);
    VacunaAplicada::factory()->for($reciénNacido)->create(['vacuna_clave' => 'bcg']);
    VacunaAplicada::factory()->for($reciénNacido)->create(['vacuna_clave' => 'hepatitis_b_rn']);

    $componente = Livewire::actingAs($coordinador)->test(VacunasAtrasadas::class);

    $nombres = $componente->instance()->ninos()->pluck('nombres');

    expect($nombres)->toContain('Atrasado')
        ->and($nombres)->not->toContain('AlDia')
        ->and($nombres)->not->toContain('ReciénNacido');
});
