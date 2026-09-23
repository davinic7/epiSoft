<?php

use App\Enums\RolInstitucional;
use App\Enums\TipoMovimientoStock;
use App\Livewire\Economato\Alertas;
use App\Models\Articulo;
use App\Models\Institucion;
use App\Models\Lote;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder a las alertas de economato', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.alertas.index'))
        ->assertForbidden();
});

test('un lote que vence dentro de los días configurados aparece en las alertas', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A', 'dias_aviso_vencimiento' => 10]);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Yogur']);
    $lote = Lote::factory()->for($articulo)->create(['fecha_vencimiento' => Carbon::today()->addDays(5)]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 3, 'fecha' => Carbon::today()]);

    Livewire::actingAs($encargado)
        ->test(Alertas::class)
        ->assertSee('Yogur');
});

test('un lote que vence fuera de los días configurados no aparece en las alertas', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A', 'dias_aviso_vencimiento' => 5]);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Conservas', 'stock_minimo' => 0]);
    $lote = Lote::factory()->for($articulo)->create(['fecha_vencimiento' => Carbon::today()->addDays(30)]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 3, 'fecha' => Carbon::today()]);

    Livewire::actingAs($encargado)
        ->test(Alertas::class)
        ->assertDontSee('Conservas');
});

test('un lote sin stock disponible no aparece como próximo a vencer', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A', 'dias_aviso_vencimiento' => 10]);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Agotado', 'stock_minimo' => 0]);
    $lote = Lote::factory()->for($articulo)->create(['fecha_vencimiento' => Carbon::today()->addDays(2)]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 3, 'fecha' => Carbon::today()]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Salida, 'cantidad' => 3, 'fecha' => Carbon::today()]);

    Livewire::actingAs($encargado)
        ->test(Alertas::class)
        ->assertDontSee('Agotado');
});

test('un artículo con stock bajo su mínimo aparece en las alertas', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Lavandina', 'stock_minimo' => 10]);
    $lote = Lote::factory()->for($articulo)->create();
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 3, 'fecha' => Carbon::today()]);

    Livewire::actingAs($encargado)
        ->test(Alertas::class)
        ->assertSee('Lavandina');
});

test('un artículo con stock por encima de su mínimo no aparece en las alertas', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Detergente', 'stock_minimo' => 5]);
    $lote = Lote::factory()->for($articulo)->create(['fecha_vencimiento' => Carbon::today()->addYear()]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 20, 'fecha' => Carbon::today()]);

    Livewire::actingAs($encargado)
        ->test(Alertas::class)
        ->assertDontSee('Detergente');
});

test('el encargado de economato puede configurar los días de aviso de vencimiento', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($encargado)
        ->test(Alertas::class)
        ->set('diasAvisoVencimiento', '15')
        ->call('guardarConfiguracion')
        ->assertHasNoErrors();

    expect($institucion->fresh()->dias_aviso_vencimiento)->toBe(15);
});

test('el personal de cocina no puede configurar los días de aviso', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($cocina)
        ->test(Alertas::class)
        ->set('diasAvisoVencimiento', '15')
        ->call('guardarConfiguracion')
        ->assertForbidden();
});
