<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Attachments;

use App\Http\Api\Requests\Attachments\UpdateAttachmentRequest;
use App\Http\Api\Responses\MessageResponse;
use App\Jobs\Attachments\UpdateAttachment;
use App\Models\Attachment;
use Illuminate\Contracts\Bus\Dispatcher;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

final readonly class UpdateController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(UpdateAttachmentRequest $request, Attachment $attachment): MessageResponse
    {
        defer(
            callback: fn () => $this->bus->dispatch(
                new UpdateAttachment(
                    attachment: $attachment,
                    payload: $request->payload($attachment),
                ),
            ),
            name: 'update-attachment',
        );

        return new MessageResponse(
            message: 'Attachment update accepted.',
            status: Response::HTTP_ACCEPTED,
        );
    }
}
