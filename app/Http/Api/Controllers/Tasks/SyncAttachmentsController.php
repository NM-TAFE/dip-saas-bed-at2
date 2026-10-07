<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Tasks;

use App\Http\Api\Requests\Tasks\SyncAttachmentsRequest;
use App\Http\Api\Responses\MessageResponse;
use App\Jobs\Tasks\SyncTaskAttachments;
use App\Models\Task;
use Illuminate\Contracts\Bus\Dispatcher;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

final readonly class SyncAttachmentsController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(
        SyncAttachmentsRequest $request,
        Task $task,
    ): MessageResponse {
        defer(
            callback: fn() => $this->bus->dispatch(
                new SyncTaskAttachments(
                    task: $task,
                    payload: $request->payload(),
                ),
            ),
            name: 'update-task-attachments',
        );

        return new MessageResponse(
            'Task attachment update accepted.',
            Response::HTTP_ACCEPTED,
        );
    }
}
