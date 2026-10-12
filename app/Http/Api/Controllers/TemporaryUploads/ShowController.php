<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\TemporaryUploads;

use App\Http\Api\Resources\TemporaryUploadResource;
use App\Models\TemporaryUpload;
use Illuminate\Http\Request;

final readonly class ShowController
{
    public function __invoke(
        Request $request,
        TemporaryUpload $temporaryUpload
    ): TemporaryUploadResource {

        abort_unless(
            (string) $temporaryUpload->uploaded_by ===
                (string) $request->user()->getKey(),
            404,
        );

        return new TemporaryUploadResource($temporaryUpload);
    }
}
