<?php

use App\Livewire\Economato\Alertas;
use App\Livewire\Economato\Bienes;
use App\Livewire\Economato\CierresMensuales;
use App\Livewire\Economato\Index;
use App\Livewire\Economato\MenuEditor;
use App\Livewire\Economato\Menus;
use App\Livewire\Economato\Movimientos;
use App\Livewire\Economato\ReporteDeConsumo;
use App\Livewire\Economato\ReporteDeInventario;
use App\Livewire\Economato\RestriccionesPorSala;
use App\Livewire\Economato\Stock;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('economato/articulos', Index::class)->name('economato.articulos.index');
    Route::livewire('economato/stock', Stock::class)->name('economato.stock.index');
    Route::livewire('economato/movimientos', Movimientos::class)->name('economato.movimientos.index');
    Route::livewire('economato/alertas', Alertas::class)->name('economato.alertas.index');
    Route::livewire('economato/menus', Menus::class)->name('economato.menus.index');
    Route::livewire('economato/menus/{menu}', MenuEditor::class)->name('economato.menus.editar');
    Route::livewire('economato/restricciones-por-sala', RestriccionesPorSala::class)->name('economato.restricciones-por-sala.index');
    Route::livewire('economato/bienes', Bienes::class)->name('economato.bienes.index');
    Route::livewire('economato/inventario', ReporteDeInventario::class)->name('economato.inventario.index');
    Route::livewire('economato/reporte-de-consumo', ReporteDeConsumo::class)->name('economato.reporte-de-consumo.index');
    Route::livewire('economato/cierres-mensuales', CierresMensuales::class)->name('economato.cierres-mensuales.index');
});
