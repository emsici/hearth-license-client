<?php

use Illuminate\Support\Facades\Route;

// Minimal web routes for interactive license management moved into the package.
Route::group(['middleware' => ['web']], function () {
    Route::get('/licente', '\\Hearth\\LicenseClient\\Controllers\\LicenseManagementController@index')
        ->name('license-client.licente.index');

    Route::post('/licente/verify', '\\Hearth\\LicenseClient\\Controllers\\LicenseManagementController@verify')
        ->name('license-client.licente.verify');

    Route::post('/licente/upload', '\\Hearth\\LicenseClient\\Controllers\\LicenseManagementController@upload')
        ->name('license-client.licente.upload');

    Route::delete('/licente', '\\Hearth\\LicenseClient\\Controllers\\LicenseManagementController@destroy')
        ->name('license-client.licente.destroy');
});
