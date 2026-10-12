<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\TemporaryUploads;

use App\Http\Api\Requests\TemporaryUploads\StoreTemporaryUploadRequest;
use App\Http\Api\Responses\TemporaryUploadAcceptedResponse;
use App\Jobs\TemporaryUploads\CreateTemporaryUpload;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

final readonly class StoreController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(
        StoreTemporaryUploadRequest $request
    ): TemporaryUploadAcceptedResponse {

        $id = (string) Str::ulid();

        defer(
            callback: fn() => $this->bus->dispatch(
                command: new CreateTemporaryUpload(
                    uploadId: $id,
                    payload: $request->payload(),
                ),
            ),
            name: 'create-temporary-upload',
        );

        return new TemporaryUploadAcceptedResponse(uploadId: $id);
    }
}
