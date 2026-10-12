<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\TemporaryUploads;

use App\Http\Api\Resources\TemporaryUploadResource;
use App\Models\TemporaryUpload;
use Illuminate\Http\Request;

final readonly class ShowController
{
    public function __invoke(Request $request, string $temporaryUpload): TemporaryUploadResource
    {
        $upload = TemporaryUpload::query()->whereKey($temporaryUpload)
            ->where('uploaded_by', (string) $request->user()->getKey())->firstOrFail();

        return new TemporaryUploadResource($upload);
    }
}
