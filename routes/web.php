<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySiteController;
use App\Http\Controllers\VelzonRoutesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::controller(VelzonRoutesController::class)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/sicurezzachiara/ui-reference', 'sicurezzachiara_ui_reference')->name('sicurezzachiara.ui-reference');
    });

    Route::resource('aziende', CompanyController::class)
        ->except(['destroy'])
        ->parameters(['aziende' => 'company'])
        ->names('companies');

    Route::controller(CompanySiteController::class)->group(function () {
        Route::post('/aziende/{company}/sedi', 'store')->name('companies.sites.store');
        Route::get('/aziende/{company}/sedi/{site}/modifica', 'edit')->name('companies.sites.edit');
        Route::put('/aziende/{company}/sedi/{site}', 'update')->name('companies.sites.update');
    });
});
