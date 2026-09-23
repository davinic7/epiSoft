<?php

use App\Livewire\Economato\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('economato/articulos', Index::class)->name('economato.articulos.index');
});
