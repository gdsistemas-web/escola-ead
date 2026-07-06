<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\ManagementExportController;

Route::view('/api/documentation', 'api.documentation')->name('api.documentation');
Route::get('/api/docs/openapi.yaml', function () {
    return response(file_get_contents(base_path('docs/openapi.yaml')), 200, [
        'Content-Type' => 'application/yaml; charset=UTF-8',
    ]);
})->name('api.openapi');

Route::get('/certificado/{code}', [CertificateController::class, 'validatePublic'])->name('certificates.validate');
Route::redirect('/admin', '/gestao');
Route::middleware('auth')->prefix('gestao/export')->name('gestao.export.')->group(function () {
    Route::get('{type}/pdf', [ManagementExportController::class, 'pdf'])->name('pdf');
    Route::get('{type}/csv', [ManagementExportController::class, 'csv'])->name('csv');
});
Route::view('/{any?}', 'app')->where('any', '.*');
