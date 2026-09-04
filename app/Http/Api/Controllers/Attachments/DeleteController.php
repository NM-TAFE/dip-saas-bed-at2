<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Attachments;

use App\Http\Api\Responses\MessageResponse;
use App\Jobs\Attachments\DeleteAttachment;
use App\Models\Attachment;
use Illuminate\Contracts\Bus\Dispatcher;

use function Illuminate\Support\defer;

use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(Attachment $attachment): MessageResponse
    {
        defer(
            callback: fn() => $this->bus->dispatch(new DeleteAttachment(attachment: $attachment)),
            name: 'delete-attachment',
        );

        return new MessageResponse(
            message: 'Attachment delete accepted.',
            status: Response::HTTP_ACCEPTED,
        );
    }
}
