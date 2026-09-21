<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Formulario;
use App\Livewire\Ninos\Index;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

function datosPasoUno(array $sobrescribir = []): array
{
    return array_merge([
        'nombres' => 'Juana',
        'apellidos' => 'Pérez',
        'alias' => 'Juani',
        'dni' => '45123456',
        'fechaNacimiento' => '2023-05-10',
        'lugarNacimiento' => 'San Fernando del Valle',
    ], $sobrescribir);
}

test('un usuario sin permiso no puede acceder al listado de niños', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('ninos.index'))
        ->assertForbidden();
});

test('un usuario sin permiso de crear no puede abrir el formulario de alta', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::CoordinadorPedagogico, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($usuario)->test(Formulario::class)->assertForbidden();
});

test('el equipo de coordinación ve el listado de niños de su institución', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    $coordinador->instituciones()->attach($institucion);

    app(InstitucionContext::class)->set($institucion->id);
    Nino::factory()->create(['nombres' => 'Juana', 'apellidos' => 'Pérez']);
    $this->flushSession();

    $this->actingAs($coordinador)
        ->get(route('ninos.index'))
        ->assertOk()
        ->assertSee('Pérez');
});

test('un niño de otra institución no aparece en el listado', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    Nino::factory()->create(['apellidos' => 'DeOtraInstitucion']);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->assertDontSee('DeOtraInstitucion');
});

test('el alta completa los tres pasos y crea el legajo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $sala = Sala::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(Formulario::class)
        ->set(datosPasoUno())
        ->call('siguiente')
        ->assertSet('paso', 2)
        ->set('domicilio', 'Calle Falsa 123')
        ->call('siguiente')
        ->assertSet('paso', 3)
        ->set('salaId', $sala->id)
        ->set('fechaIngreso', '2026-01-15')
        ->call('guardar')
        ->assertHasNoErrors();

    $nino = Nino::where('dni', '45123456')->sole();
    expect($nino->nombres)->toBe('Juana')
        ->and($nino->domicilio)->toBe('Calle Falsa 123')
        ->and($nino->sala_id)->toBe($sala->id);
});

test('el paso uno exige los campos obligatorios y no avanza', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($coordinador)
        ->test(Formulario::class)
        ->set(datosPasoUno(['nombres' => '', 'apellidos' => '', 'dni' => '', 'fechaNacimiento' => '']))
        ->call('siguiente')
        ->assertHasErrors(['nombres', 'apellidos', 'dni', 'fechaNacimiento'])
        ->assertSet('paso', 1);
});

test('el dni debe ser único dentro de la institución', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Nino::factory()->create(['dni' => '45123456']);

    Livewire::actingAs($coordinador)
        ->test(Formulario::class)
        ->set(datosPasoUno(['dni' => '45123456']))
        ->call('siguiente')
        ->assertHasErrors(['dni']);
});

test('el mismo dni puede repetirse en otra institución', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);

    app(InstitucionContext::class)->set($epiB->id);
    Nino::factory()->create(['dni' => '45123456']);

    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $epiA);
    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($coordinador)
        ->test(Formulario::class)
        ->set(datosPasoUno(['dni' => '45123456']))
        ->call('siguiente')
        ->assertHasNoErrors();
});

test('la sala elegida en el paso tres debe pertenecer a la institución activa', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    $salaAjena = Sala::factory()->create();

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($coordinador)
        ->test(Formulario::class)
        ->set(datosPasoUno())
        ->call('siguiente')
        ->set('domicilio', 'Calle Falsa 123')
        ->call('siguiente')
        ->set('salaId', $salaAjena->id)
        ->set('fechaIngreso', '2026-01-15')
        ->call('guardar')
        ->assertHasErrors(['salaId']);
});

test('el equipo de coordinación puede editar un legajo existente', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['nombres' => 'Juana', 'domicilio' => 'Domicilio viejo']);

    Livewire::actingAs($coordinador)
        ->test(Formulario::class, ['nino' => $nino->id])
        ->assertSet('nombres', 'Juana')
        ->assertSet('soloLectura', false)
        ->set('domicilio', 'Domicilio nuevo')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($nino->fresh()->domicilio)->toBe('Domicilio nuevo');
});

test('abrir un legajo registra un acceso en la auditoría', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create();

    Livewire::actingAs($coordinador)->test(Formulario::class, ['nino' => $nino->id]);

    $registro = Audit::query()
        ->where('event', 'acceso')
        ->where('auditable_type', Nino::class)
        ->where('auditable_id', $nino->id)
        ->sole();

    expect($registro->user_id)->toBe($coordinador->id);
});

test('el coordinador pedagógico solo puede consultar un legajo, no editarlo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinadorPedagogico = usuarioConRol(RolInstitucional::CoordinadorPedagogico, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $nino = Nino::factory()->create(['domicilio' => 'Domicilio original']);

    Livewire::actingAs($coordinadorPedagogico)
        ->test(Formulario::class, ['nino' => $nino->id])
        ->assertSet('soloLectura', true)
        ->set('domicilio', 'Intento de cambio')
        ->call('guardar')
        ->assertForbidden();

    expect($nino->fresh()->domicilio)->toBe('Domicilio original');
});

test('el listado de niños pagina', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Nino::factory()->count(16)->create();

    $componente = Livewire::actingAs($coordinador)->test(Index::class);

    expect($componente->instance()->ninos()->total())->toBe(16)
        ->and($componente->instance()->ninos()->perPage())->toBe(15);
});
