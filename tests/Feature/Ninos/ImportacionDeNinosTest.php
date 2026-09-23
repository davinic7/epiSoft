<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\Importar;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function archivoCsv(string $contenido): File
{
    return UploadedFile::fake()->createWithContent('ninos.csv', $contenido);
}

function filaCsv(array $sobrescribir = []): array
{
    return array_merge([
        'nombres' => 'Juana',
        'apellidos' => 'Pérez',
        'alias' => 'Juani',
        'dni' => '45123456',
        'fecha_nacimiento' => '2023-05-10',
        'lugar_nacimiento' => 'San Fernando del Valle',
        'domicilio' => 'Calle Falsa 123',
        'fecha_ingreso' => '2026-01-15',
        'sala' => '',
    ], $sobrescribir);
}

function csvDesdeFilas(array $filas): string
{
    $encabezado = ['nombres', 'apellidos', 'alias', 'dni', 'fecha_nacimiento', 'lugar_nacimiento', 'domicilio', 'fecha_ingreso', 'sala'];

    $lineas = [implode(',', $encabezado)];

    foreach ($filas as $fila) {
        $lineas[] = implode(',', array_map(
            fn ($columna) => '"'.str_replace('"', '""', $fila[$columna] ?? '').'"',
            $encabezado
        ));
    }

    return implode("\n", $lineas);
}

test('un usuario sin permiso de crear no puede acceder a la importación', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $educador = usuarioConRol(RolInstitucional::CoordinadorPedagogico, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($educador)->test(Importar::class)->assertForbidden();
});

test('importa filas válidas y reporta las rechazadas con el motivo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $sala = Sala::factory()->create(['nombre' => 'Sala de bebés']);

    $csv = csvDesdeFilas([
        filaCsv(['sala' => 'Sala de bebés']),
        filaCsv(['nombres' => '', 'apellidos' => 'Sin nombre', 'dni' => '11111111']),
        filaCsv(['apellidos' => 'Fecha mala', 'dni' => '22222222', 'fecha_nacimiento' => '2023-13-40']),
    ]);

    $resultado = Livewire::actingAs($coordinador)
        ->test(Importar::class)
        ->set('archivo', archivoCsv($csv))
        ->call('importar')
        ->assertHasNoErrors()
        ->get('resultado');

    expect($resultado)->toHaveCount(3)
        ->and($resultado[0]['estado'])->toBe('importada')
        ->and($resultado[1]['estado'])->toBe('rechazada')
        ->and($resultado[2]['estado'])->toBe('rechazada');

    $nino = Nino::where('dni', '45123456')->sole();
    expect($nino->nombres)->toBe('Juana')
        ->and($nino->sala_id)->toBe($sala->id)
        ->and(Nino::where('dni', '11111111')->exists())->toBeFalse()
        ->and(Nino::where('dni', '22222222')->exists())->toBeFalse();
});

test('rechaza una fila con una sala que no existe', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $csv = csvDesdeFilas([filaCsv(['sala' => 'Sala inexistente'])]);

    $resultado = Livewire::actingAs($coordinador)
        ->test(Importar::class)
        ->set('archivo', archivoCsv($csv))
        ->call('importar')
        ->get('resultado');

    expect($resultado[0]['estado'])->toBe('rechazada')
        ->and($resultado[0]['motivo'])->toContain('Sala inexistente');
});

test('rechaza un dni repetido dentro de la misma planilla', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $csv = csvDesdeFilas([
        filaCsv(['apellidos' => 'Primero']),
        filaCsv(['apellidos' => 'Repetido']),
    ]);

    $resultado = Livewire::actingAs($coordinador)
        ->test(Importar::class)
        ->set('archivo', archivoCsv($csv))
        ->call('importar')
        ->get('resultado');

    expect($resultado[0]['estado'])->toBe('importada')
        ->and($resultado[1]['estado'])->toBe('rechazada')
        ->and(Nino::where('dni', '45123456')->count())->toBe(1);
});

test('no duplica a un niño ya existente, incluso dado de baja, al reimportar', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $nino = Nino::factory()->create(['dni' => '45123456']);
    $nino->darDeBaja('Motivo', Carbon::today());

    $csv = csvDesdeFilas([filaCsv()]);

    $resultado = Livewire::actingAs($coordinador)
        ->test(Importar::class)
        ->set('archivo', archivoCsv($csv))
        ->call('importar')
        ->get('resultado');

    expect($resultado[0]['estado'])->toBe('rechazada')
        ->and(Nino::withTrashed()->where('dni', '45123456')->count())->toBe(1);
});

test('descargar la plantilla devuelve un csv', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($coordinador)
        ->test(Importar::class)
        ->call('descargarPlantilla')
        ->assertFileDownloaded('plantilla-ninos.csv');
});
