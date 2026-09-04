<?php

declare(strict_types=1);

namespace App\Jobs\Tasks;

use App\Http\Payloads\Tasks\NewTask;
use App\Models\Task;

final readonly class UpdateTask
{
    public function __construct(
        public Task $task,
        public NewTask $payload,
    ) {}

    public function handle(): Task
    {
        $this->task->update($this->payload->toArray());

        return $this->task->refresh();
    }
}
