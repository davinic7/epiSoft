<?php

use App\Enums\RolInstitucional;
use App\Enums\Turno;
use App\Livewire\Ninos\Index;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Livewire\Livewire;

test('la búsqueda encuentra por nombre, apellido, alias y dni', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Nino::factory()->create(['nombres' => 'Juana', 'apellidos' => 'Pérez', 'alias' => 'Juani', 'dni' => '45111111']);
    Nino::factory()->create(['nombres' => 'Pedro', 'apellidos' => 'Gómez', 'alias' => null, 'dni' => '45222222']);

    $componente = Livewire::actingAs($coordinador)->test(Index::class);

    $componente->set('busqueda', 'Juana')->assertSee('Pérez')->assertDontSee('Gómez');
    $componente->set('busqueda', 'Gómez')->assertSee('Gómez')->assertDontSee('Pérez');
    $componente->set('busqueda', 'Juani')->assertSee('Pérez')->assertDontSee('Gómez');
    $componente->set('busqueda', '45222222')->assertSee('Gómez')->assertDontSee('Pérez');
});

test('el filtro de sala muestra solo a los niños de esa sala', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $salaA = Sala::factory()->create(['nombre' => 'Sala A']);
    $salaB = Sala::factory()->create(['nombre' => 'Sala B']);
    Nino::factory()->create(['apellidos' => 'DeSalaA', 'sala_id' => $salaA->id]);
    Nino::factory()->create(['apellidos' => 'DeSalaB', 'sala_id' => $salaB->id]);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('salaId', (string) $salaA->id)
        ->assertSee('DeSalaA')
        ->assertDontSee('DeSalaB');
});

test('el filtro de turno muestra a los niños de todas las salas de ese turno', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $salaManana = Sala::factory()->create(['turno' => Turno::Manana]);
    $salaTarde = Sala::factory()->create(['turno' => Turno::Tarde]);
    Nino::factory()->create(['apellidos' => 'DeManana', 'sala_id' => $salaManana->id]);
    Nino::factory()->create(['apellidos' => 'DeTarde', 'sala_id' => $salaTarde->id]);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('turno', Turno::Manana->value)
        ->assertSee('DeManana')
        ->assertDontSee('DeTarde');
});

test('por defecto el listado no muestra niños dados de baja', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Nino::factory()->create(['apellidos' => 'Matriculado']);
    $deBaja = Nino::factory()->create(['apellidos' => 'DeBaja']);
    $deBaja->delete();

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->assertSee('Matriculado')
        ->assertDontSee('DeBaja');
});

test('el filtro de estado "de baja" muestra solo a los dados de baja', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Nino::factory()->create(['apellidos' => 'Matriculado']);
    $deBaja = Nino::factory()->create(['apellidos' => 'DeBaja']);
    $deBaja->delete();

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('estado', 'baja')
        ->assertSee('DeBaja')
        ->assertDontSee('Matriculado');
});

test('el filtro de estado "todos" muestra activos y dados de baja', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Nino::factory()->create(['apellidos' => 'Matriculado']);
    $deBaja = Nino::factory()->create(['apellidos' => 'DeBaja']);
    $deBaja->delete();

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->set('estado', 'todos')
        ->assertSee('Matriculado')
        ->assertSee('DeBaja');
});
