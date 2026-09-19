<?php

use App\Livewire\Instituciones\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('instituciones', Index::class)->name('instituciones.index');
});
