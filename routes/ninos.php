<?php

use App\Livewire\Ninos\Alergias;
use App\Livewire\Ninos\Formulario;
use App\Livewire\Ninos\Index;
use App\Livewire\Ninos\Referentes;
use App\Livewire\Ninos\Vacunas;
use App\Livewire\Ninos\VacunasAtrasadas;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('ninos', Index::class)->name('ninos.index');
    Route::livewire('ninos/nuevo', Formulario::class)->name('ninos.crear');
    Route::livewire('ninos/vacunas-atrasadas', VacunasAtrasadas::class)->name('ninos.vacunas-atrasadas');
    Route::livewire('ninos/{nino}/editar', Formulario::class)->name('ninos.editar');
    Route::livewire('ninos/{nino}/referentes', Referentes::class)->name('ninos.referentes');
    Route::livewire('ninos/{nino}/vacunas', Vacunas::class)->name('ninos.vacunas');
    Route::livewire('ninos/{nino}/alergias', Alergias::class)->name('ninos.alergias');
});
