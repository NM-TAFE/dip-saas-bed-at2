<?php

declare(strict_types=1);

namespace App\Jobs\Tasks;

use App\Http\Payloads\Tasks\NewTask;
use App\Jobs\Webhooks\SendTaskCreatedWebhook;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CreateNewTask implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NewTask $payload) {}

    public function handle(): Task
    {
        $task = Task::query()->create(
            $this->payload->toArray()
        );

        // NOTE:  the test route needs to be switched on first
        // SendTaskCreatedWebhook::dispatch($task);

        return $task;
    }
}
