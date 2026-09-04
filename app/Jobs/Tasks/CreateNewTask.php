<?php

declare(strict_types=1);

namespace App\Jobs\Tasks;

use App\Http\Payloads\Tasks\NewTask;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Jobs\Webhooks\SendTaskCreatedWebhook;

final class CreateNewTask implements ShouldQueue
{
    use Queueable;

    public function __construct(public NewTask $payload) {}

    public function handle(): Task
    {
        $task = Task::query()->create(
            $this->payload->toArray()
        );

        SendTaskCreatedWebhook::dispatch($task);

        return $task;
    }
}
