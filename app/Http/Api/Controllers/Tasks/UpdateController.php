<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Tasks;

use App\Http\Api\Requests\Tasks\UpdateTaskRequest;
use App\Http\Api\Responses\MessageResponse;
use App\Jobs\Tasks\UpdateTask;
use App\Models\Task;
use Illuminate\Contracts\Bus\Dispatcher;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

final readonly class UpdateController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(UpdateTaskRequest $request, Task $task): MessageResponse
    {
        defer(
            callback: fn () => $this->bus->dispatch(
                new UpdateTask(task: $task, payload: $request->payload($task)),
            ),
            name: 'update-task',
        );

        return new MessageResponse('Task update accepted.', Response::HTTP_ACCEPTED);
    }
}
