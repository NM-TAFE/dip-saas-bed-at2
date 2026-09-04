<?php

declare(strict_types=1);

namespace App\Jobs\Tasks;

use App\Models\Attachment;
use App\Models\Task;
use App\Services\AttachmentStorageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

final class DeleteTask implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Task $task) {}

    public function handle(AttachmentStorageService $attachmentStorage): void
    {
        // TODO What else needs to be deleted

        /** @var Collection<int, Attachment> $attachments */
        $attachments = $this->task->attachments()->get();
        $attachments->each(function (Attachment $attachment) use ($attachmentStorage): void {
            $attachmentStorage->delete($attachment);
            $attachment->delete();
        });

        $this->task->delete();
    }
}
