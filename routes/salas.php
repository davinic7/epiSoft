<?php

use App\Livewire\Salas\Asistencia;
use App\Livewire\Salas\Index;
use App\Livewire\Salas\Ver;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('salas', Index::class)->name('salas.index');
    Route::livewire('salas/{sala}', Ver::class)->name('salas.ver');
    Route::livewire('salas/{sala}/asistencia', Asistencia::class)->name('salas.asistencia');
});
