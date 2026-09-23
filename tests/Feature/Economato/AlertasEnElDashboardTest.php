<?php

use App\Enums\RolInstitucional;
use App\Enums\TipoMovimientoStock;
use App\Models\Articulo;
use App\Models\Institucion;
use App\Models\Lote;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;

test('el dashboard muestra las alertas de economato a quien tiene permiso', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A', 'dias_aviso_vencimiento' => 10]);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    $encargado->instituciones()->attach($institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $articulo = Articulo::factory()->create(['nombre' => 'Yogur']);
    $lote = Lote::factory()->for($articulo)->create(['fecha_vencimiento' => Carbon::today()->addDays(3)]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 3, 'fecha' => Carbon::today()]);
    $this->flushSession();

    $this->actingAs($encargado)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Yogur');
});

test('el dashboard no muestra alertas de economato a quien no tiene el permiso', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $educador = usuarioConRol(RolInstitucional::Educador, $institucion);
    $educador->instituciones()->attach($institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $this->flushSession();

    $this->actingAs($educador)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Alertas de economato');
});
