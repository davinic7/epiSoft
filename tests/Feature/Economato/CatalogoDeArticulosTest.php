<?php

use App\Enums\CategoriaArticulo;
use App\Enums\RolInstitucional;
use App\Livewire\Economato\Index;
use App\Models\Articulo;
use App\Models\Institucion;
use App\Support\InstitucionContext;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder al catálogo de artículos', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.articulos.index'))
        ->assertForbidden();
});

test('el personal de cocina puede ver el catálogo pero no crear artículos', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $institucion);
    $cocina->instituciones()->attach($institucion);

    app(InstitucionContext::class)->set($institucion->id);
    Articulo::factory()->create(['nombre' => 'Arroz']);
    $this->flushSession();

    $this->actingAs($cocina)
        ->get(route('economato.articulos.index'))
        ->assertOk()
        ->assertSee('Arroz');

    Livewire::actingAs($cocina)
        ->test(Index::class)
        ->call('nuevo')
        ->assertForbidden();
});

test('el encargado de economato puede crear un artículo con categoría, unidad y stock mínimo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($encargado)
        ->test(Index::class)
        ->set('nombre', 'Arroz')
        ->set('categoria', CategoriaArticulo::Alimentos->value)
        ->set('unidadMedida', 'kg')
        ->set('stockMinimo', '5.5')
        ->call('guardar')
        ->assertHasNoErrors();

    $articulo = Articulo::where('nombre', 'Arroz')->sole();
    expect($articulo->categoria)->toBe(CategoriaArticulo::Alimentos)
        ->and($articulo->unidad_medida)->toBe('kg')
        ->and((float) $articulo->stock_minimo)->toBe(5.5);
});

test('crear un artículo exige los campos obligatorios', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($encargado)
        ->test(Index::class)
        ->set('nombre', '')
        ->set('categoria', '')
        ->set('unidadMedida', '')
        ->set('stockMinimo', '')
        ->call('guardar')
        ->assertHasErrors(['nombre', 'categoria', 'unidadMedida', 'stockMinimo']);
});

test('el nombre de un artículo debe ser único dentro de la institución', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Articulo::factory()->create(['nombre' => 'Arroz']);

    Livewire::actingAs($encargado)
        ->test(Index::class)
        ->set('nombre', 'Arroz')
        ->set('categoria', CategoriaArticulo::Alimentos->value)
        ->set('unidadMedida', 'kg')
        ->set('stockMinimo', '5')
        ->call('guardar')
        ->assertHasErrors(['nombre']);
});

test('el mismo nombre de artículo puede repetirse en otra institución', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);

    app(InstitucionContext::class)->set($epiB->id);
    Articulo::factory()->create(['nombre' => 'Arroz']);

    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);
    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(Index::class)
        ->set('nombre', 'Arroz')
        ->set('categoria', CategoriaArticulo::Alimentos->value)
        ->set('unidadMedida', 'kg')
        ->set('stockMinimo', '5')
        ->call('guardar')
        ->assertHasNoErrors();
});

test('el encargado de economato puede editar un artículo existente', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $articulo = Articulo::factory()->create(['nombre' => 'Arroz', 'stock_minimo' => 5]);

    Livewire::actingAs($encargado)
        ->test(Index::class)
        ->call('editar', $articulo->id)
        ->assertSet('nombre', 'Arroz')
        ->set('stockMinimo', '20')
        ->call('guardar')
        ->assertHasNoErrors();

    expect((float) $articulo->fresh()->stock_minimo)->toBe(20.0);
});

test('un artículo de otra institución no aparece en el listado', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    Articulo::factory()->create(['nombre' => 'Artículo de EPI B']);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(Index::class)
        ->assertDontSee('Artículo de EPI B');
});

test('la búsqueda filtra por nombre y el filtro por categoría', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Articulo::factory()->create(['nombre' => 'Fideos', 'categoria' => CategoriaArticulo::Alimentos]);
    Articulo::factory()->create(['nombre' => 'Lavandina', 'categoria' => CategoriaArticulo::Limpieza]);

    Livewire::actingAs($encargado)
        ->test(Index::class)
        ->set('busqueda', 'Fid')
        ->assertSee('Fideos')
        ->assertDontSee('Lavandina')
        ->set('busqueda', '')
        ->set('filtroCategoria', CategoriaArticulo::Limpieza->value)
        ->assertSee('Lavandina')
        ->assertDontSee('Fideos');
});
