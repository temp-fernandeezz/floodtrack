<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FloodPointPublicController;

Route::get('/', [FloodPointPublicController::class, 'index'])->name('home');
Route::get('/pontos/{floodPoint}', [FloodPointPublicController::class, 'show'])->name('flood-points.show');

// endpoint JSON pro mapa
Route::get('/api/flood-points', [FloodPointPublicController::class, 'api'])->name('flood-points.api');
Route::get('/api/flood-points/pending', [FloodPointPublicController::class, 'apiPending'])
    ->name('flood-points.apiPending');

