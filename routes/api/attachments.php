<?php

declare(strict_types=1);

use App\Http\Api\Controllers\Attachments\DeleteController;
use App\Http\Api\Controllers\Attachments\ShowController;
use App\Http\Api\Controllers\Attachments\StoreController;
use App\Http\Api\Controllers\Attachments\UpdateController;
use App\Http\Api\Resources\AttachmentResource;
use App\Http\Api\Responses\PaginatedCollectionResponse;
use App\Models\Attachment;
use App\Support\Pagination;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $attachments = Pagination::simple(
        Attachment::query()->with(['uploadedBy']),
    );

    return new PaginatedCollectionResponse(
        data: AttachmentResource::collection($attachments),
    );
})->name('index');

Route::post('/', StoreController::class)->name('store');
Route::put('/{attachment}', UpdateController::class)->whereUlid('attachment')->name('update');
Route::get('/{attachment}', ShowController::class)->whereUlid('attachment')->name('show');
Route::delete('/{attachment}', DeleteController::class)->whereUlid('attachment')->name('delete');
