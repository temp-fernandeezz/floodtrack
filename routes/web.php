<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FloodPointPublicController;
use App\Http\Controllers\FloodReportController;
use App\Http\Controllers\RainfallController;

Route::get('/', [FloodPointPublicController::class, 'index'])->name('home');
Route::get('/estatisticas', [FloodPointPublicController::class, 'stats'])->name('stats');
Route::get('/estatisticas/risco.csv', [FloodPointPublicController::class, 'exportRisk'])->name('stats.risk-export');
Route::get('/pontos/exportar.csv', [FloodPointPublicController::class, 'export'])->name('flood-points.export');
Route::get('/pontos/{floodPoint}', [FloodPointPublicController::class, 'show'])->name('flood-points.show');

Route::get('/reportar', [FloodReportController::class, 'create'])->name('flood-points.report.create');
Route::post('/reportar', [FloodReportController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('flood-points.report.store');

// endpoint JSON pro mapa
Route::get('/api/flood-points', [FloodPointPublicController::class, 'api'])->name('flood-points.api');
Route::get('/api/flood-points/pending', [FloodPointPublicController::class, 'apiPending'])
    ->name('flood-points.apiPending');
Route::get('/api/cidades', [FloodPointPublicController::class, 'cidades'])->name('flood-points.cidades');
Route::get('/api/chuva', [RainfallController::class, 'show'])->name('rainfall.show');

