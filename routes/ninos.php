<?php

use App\Livewire\Ninos\Index;
use App\Livewire\Ninos\Show;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('ninos', Index::class)->name('ninos.index');
    Route::livewire('ninos/{nino}', Show::class)->name('ninos.show');
});
