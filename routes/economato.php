<?php

use App\Livewire\Economato\Alertas;
use App\Livewire\Economato\Index;
use App\Livewire\Economato\MenuEditor;
use App\Livewire\Economato\Menus;
use App\Livewire\Economato\Movimientos;
use App\Livewire\Economato\Stock;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('economato/articulos', Index::class)->name('economato.articulos.index');
    Route::livewire('economato/stock', Stock::class)->name('economato.stock.index');
    Route::livewire('economato/movimientos', Movimientos::class)->name('economato.movimientos.index');
    Route::livewire('economato/alertas', Alertas::class)->name('economato.alertas.index');
    Route::livewire('economato/menus', Menus::class)->name('economato.menus.index');
    Route::livewire('economato/menus/{menu}', MenuEditor::class)->name('economato.menus.editar');
});
