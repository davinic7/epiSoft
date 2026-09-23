<?php

use App\Livewire\Economato\Index;
use App\Livewire\Economato\Movimientos;
use App\Livewire\Economato\Stock;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('economato/articulos', Index::class)->name('economato.articulos.index');
    Route::livewire('economato/stock', Stock::class)->name('economato.stock.index');
    Route::livewire('economato/movimientos', Movimientos::class)->name('economato.movimientos.index');
});
