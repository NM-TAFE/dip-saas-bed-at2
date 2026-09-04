<?php

declare(strict_types=1);

namespace App\Jobs\Tasks;

use App\Http\Payloads\Tasks\NewTask;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class UpdateTask implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly NewTask $payload,
    ) {}

    public function handle(): Task
    {
        $this->task->update($this->payload->toArray());

        return $this->task->refresh();
    }
}
