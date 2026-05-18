<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDvrReportController;
use App\Http\Controllers\CompanySiteController;
use App\Http\Controllers\Ateco2025Controller;
use App\Http\Controllers\ComuneElencoController;
use App\Http\Controllers\EquipmentAssetController;
use App\Http\Controllers\EquipmentTypeController;
use App\Http\Controllers\JobRoleController;
use App\Http\Controllers\MeasureRegistryController;
use App\Http\Controllers\RiskCatalogItemController;
use App\Http\Controllers\RiskMeasureController;
use App\Http\Controllers\RiskProfileController;
use App\Http\Controllers\RiskProfileReviewController;
use App\Http\Controllers\RiskSourceLinkController;
use App\Http\Controllers\VelzonRoutesController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\WorkerEquipmentExposureController;
use App\Http\Controllers\WorkerJobRoleAssignmentController;
use App\Http\Controllers\WorkerWorkplaceExposureController;
use App\Http\Controllers\WorkplaceController;
use App\Http\Controllers\WorkplaceTypeController;
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

    Route::get('/cataloghi/ateco/ricerca', [Ateco2025Controller::class, 'search'])
        ->name('ateco.search');

    Route::get('/cataloghi/comuni/ricerca', [ComuneElencoController::class, 'search'])
        ->name('comuni.search');

    Route::controller(CompanySiteController::class)->group(function () {
        Route::post('/aziende/{company}/sedi', 'store')->name('companies.sites.store');
        Route::get('/aziende/{company}/sedi/{site}/modifica', 'edit')->name('companies.sites.edit');
        Route::put('/aziende/{company}/sedi/{site}', 'update')->name('companies.sites.update');
        Route::delete('/aziende/{company}/sedi/{site}', 'destroy')->name('companies.sites.destroy');
    });

    Route::get('/aziende/{company}/dvr-iniziale', [CompanyDvrReportController::class, 'show'])
        ->name('companies.dvr.show');

    Route::resource('lavoratori', WorkerController::class)
        ->except([])
        ->parameters(['lavoratori' => 'worker'])
        ->names('workers');

    Route::resource('mansioni', JobRoleController::class)
        ->except(['destroy'])
        ->parameters(['mansioni' => 'jobRole'])
        ->names('job-roles');

    Route::resource('catalogo-macchinari', EquipmentTypeController::class)
        ->except(['destroy'])
        ->parameters(['catalogo-macchinari' => 'equipmentType'])
        ->names('equipment-types');

    Route::resource('macchinari', EquipmentAssetController::class)
        ->except([])
        ->parameters(['macchinari' => 'equipmentAsset'])
        ->names('equipment-assets');

    Route::resource('catalogo-luoghi', WorkplaceTypeController::class)
        ->except(['destroy'])
        ->parameters(['catalogo-luoghi' => 'workplaceType'])
        ->names('workplace-types');

    Route::resource('luoghi', WorkplaceController::class)
        ->except([])
        ->parameters(['luoghi' => 'workplace'])
        ->names('workplaces');

    Route::resource('rischi', RiskCatalogItemController::class)
        ->except(['destroy'])
        ->parameters(['rischi' => 'riskCatalogItem'])
        ->names('risk-catalog');

    Route::get('/registri/misure', [MeasureRegistryController::class, 'index'])
        ->name('measure-registries.index');

    Route::controller(RiskProfileController::class)->group(function () {
        Route::get('/aziende/{company}/profilo-rischio', 'showCompany')->name('companies.risk-profile.show');
        Route::get('/lavoratori/{worker}/profilo-rischio', 'showWorker')->name('workers.risk-profile.show');
    });

    Route::controller(RiskProfileReviewController::class)->group(function () {
        Route::post('/aziende/{company}/profilo-rischio/manuale', 'storeManualCompany')
            ->name('companies.risk-profile.manual.store');
        Route::get('/aziende/{company}/profilo-rischio/{riskProfileItem}/valutazione', 'showCompany')
            ->name('companies.risk-profile.review.show');
        Route::put('/aziende/{company}/profilo-rischio/{riskProfileItem}/valutazione', 'updateCompany')
            ->name('companies.risk-profile.review.update');

        Route::post('/lavoratori/{worker}/profilo-rischio/manuale', 'storeManualWorker')
            ->name('workers.risk-profile.manual.store');
        Route::get('/lavoratori/{worker}/profilo-rischio/{riskProfileItem}/valutazione', 'showWorker')
            ->name('workers.risk-profile.review.show');
        Route::put('/lavoratori/{worker}/profilo-rischio/{riskProfileItem}/valutazione', 'updateWorker')
            ->name('workers.risk-profile.review.update');
    });

    Route::controller(RiskMeasureController::class)->group(function () {
        Route::get('/aziende/{company}/profilo-rischio/{riskProfileItem}/misure', 'showCompany')
            ->name('companies.risk-profile.measures.show');
        Route::post('/aziende/{company}/profilo-rischio/{riskProfileItem}/misure', 'storeCompany')
            ->name('companies.risk-profile.measures.store');
        Route::put('/aziende/{company}/profilo-rischio/{riskProfileItem}/misure/{riskMeasure}', 'updateCompany')
            ->name('companies.risk-profile.measures.update');
        Route::delete('/aziende/{company}/profilo-rischio/{riskProfileItem}/misure/{riskMeasure}', 'destroyCompany')
            ->name('companies.risk-profile.measures.destroy');

        Route::get('/lavoratori/{worker}/profilo-rischio/{riskProfileItem}/misure', 'showWorker')
            ->name('workers.risk-profile.measures.show');
        Route::post('/lavoratori/{worker}/profilo-rischio/{riskProfileItem}/misure', 'storeWorker')
            ->name('workers.risk-profile.measures.store');
        Route::put('/lavoratori/{worker}/profilo-rischio/{riskProfileItem}/misure/{riskMeasure}', 'updateWorker')
            ->name('workers.risk-profile.measures.update');
        Route::delete('/lavoratori/{worker}/profilo-rischio/{riskProfileItem}/misure/{riskMeasure}', 'destroyWorker')
            ->name('workers.risk-profile.measures.destroy');
    });

    Route::controller(WorkerJobRoleAssignmentController::class)->group(function () {
        Route::post('/lavoratori/{worker}/mansioni', 'store')->name('workers.job-role-assignments.store');
        Route::put('/lavoratori/{worker}/mansioni/{assignment}', 'update')->name('workers.job-role-assignments.update');
        Route::delete('/lavoratori/{worker}/mansioni/{assignment}', 'destroy')->name('workers.job-role-assignments.destroy');
    });

    Route::controller(WorkerEquipmentExposureController::class)->group(function () {
        Route::post('/lavoratori/{worker}/macchinari', 'store')->name('workers.equipment-exposures.store');
        Route::put('/lavoratori/{worker}/macchinari/{workerEquipmentExposure}', 'update')->name('workers.equipment-exposures.update');
        Route::delete('/lavoratori/{worker}/macchinari/{workerEquipmentExposure}', 'destroy')->name('workers.equipment-exposures.destroy');
    });

    Route::controller(WorkerWorkplaceExposureController::class)->group(function () {
        Route::post('/lavoratori/{worker}/luoghi', 'store')->name('workers.workplace-exposures.store');
        Route::put('/lavoratori/{worker}/luoghi/{workerWorkplaceExposure}', 'update')->name('workers.workplace-exposures.update');
        Route::delete('/lavoratori/{worker}/luoghi/{workerWorkplaceExposure}', 'destroy')->name('workers.workplace-exposures.destroy');
    });

    Route::controller(RiskSourceLinkController::class)->group(function () {
        Route::post('/rischi/{riskCatalogItem}/collegamenti', 'store')->name('risk-catalog.source-links.store');
        Route::delete('/rischi/{riskCatalogItem}/collegamenti/{riskSourceLink}', 'destroy')->name('risk-catalog.source-links.destroy');
    });
});
