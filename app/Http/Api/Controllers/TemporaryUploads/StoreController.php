<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\TemporaryUploads;

use App\Http\Api\Requests\TemporaryUploads\StoreTemporaryUploadRequest;
use App\Http\Api\Responses\MessageResponse;
use App\Jobs\TemporaryUploads\CreateTemporaryUpload;
use Illuminate\Contracts\Bus\Dispatcher;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

final readonly class StoreController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(StoreTemporaryUploadRequest $request): MessageResponse
    {
        $payload = $request->payload();

        defer(
            callback: fn() => $this->bus->dispatch(
                command: new CreateTemporaryUpload(payload: $payload),
            ),
            name: 'create-temporary-upload',
        );

        return new MessageResponse(
            message: 'We have accepted your request.',
            status: Response::HTTP_ACCEPTED,
            metadata: ['id' => $payload->id],
        );
    }
}
