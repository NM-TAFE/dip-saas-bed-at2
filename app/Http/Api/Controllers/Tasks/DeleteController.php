<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Tasks;

use App\Http\Api\Responses\MessageResponse;
use App\Jobs\Tasks\DeleteTask;
use App\Models\Task;
use Illuminate\Contracts\Bus\Dispatcher;

use function Illuminate\Support\defer;

use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(Task $task): MessageResponse
    {
        defer(
            callback: fn() => $this->bus->dispatch(new DeleteTask(task: $task)),
            name: 'delete-Task',
        );

        return new MessageResponse('Task delete accepted.', Response::HTTP_ACCEPTED);
    }
}
