<?php

declare(strict_types=1);

use App\Http\Api\Controllers\TemporaryUploads\ShowController;
use App\Http\Api\Controllers\TemporaryUploads\StoreController;
use Illuminate\Support\Facades\Route;

Route::post('/', StoreController::class)->name('temporary-uploads.store');
Route::get('/{temporaryUpload}', ShowController::class)->whereUlid('temporaryUpload')->name('temporary-uploads.show');
