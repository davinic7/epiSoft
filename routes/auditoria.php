<?php

use App\Livewire\Auditoria\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('auditoria', Index::class)->name('auditoria.index');
});
