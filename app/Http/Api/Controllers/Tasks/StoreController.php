<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Tasks;

use App\Http\Api\Requests\Tasks\StoreTaskRequest;
use App\Http\Api\Responses\MessageResponse;
use App\Jobs\Tasks\CreateNewTask;
use Illuminate\Contracts\Bus\Dispatcher;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

final readonly class StoreController
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(StoreTaskRequest $request): MessageResponse
    {
        defer(
            callback: fn () => $this->bus->dispatch(
                command: new CreateNewTask(payload: $request->payload()),
            ),
            name: 'create-new-task',
        );

        return new MessageResponse(message: 'We have accepted your request.', status: Response::HTTP_ACCEPTED);
    }
}
