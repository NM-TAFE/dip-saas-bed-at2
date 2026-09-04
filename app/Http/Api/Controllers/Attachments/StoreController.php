<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Attachments;

use App\Http\Api\Requests\Attachments\StoreAttachmentRequest;
use App\Http\Api\Responses\MessageResponse;
use App\Jobs\Attachments\CreateNewAttachment;
use Illuminate\Contracts\Bus\Dispatcher;

use function Illuminate\Support\defer;

use Symfony\Component\HttpFoundation\Response;

final readonly class StoreController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(StoreAttachmentRequest $request): MessageResponse
    {
        defer(
            callback: fn() => $this->bus->dispatch(
                command: new CreateNewAttachment(payload: $request->payload()),
            ),
            name: 'create-new-attachment',
        );

        return new MessageResponse(message: 'We have accepted your request.', status: Response::HTTP_ACCEPTED);
    }
}
