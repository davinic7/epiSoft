<?php

use App\Livewire\Salas\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('salas', Index::class)->name('salas.index');
});
