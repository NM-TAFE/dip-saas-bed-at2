<?php

declare(strict_types=1);

namespace App\Jobs\Tasks;

use App\Http\Payloads\Tasks\SyncTaskAttachments as SyncTaskAttachmentsPayload;
use App\Models\Attachment;
use App\Models\Task;
use App\Support\PolymorphicRelations;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncTaskAttachments implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly SyncTaskAttachmentsPayload $payload,
    ) {}

    public function handle(): void
    {
        $requestedIds = collect($this->payload->attachmentIds)
            ->unique()
            ->values();
        // Attach every requested Attachment to this Task.
        Attachment::query()
            ->whereIn('_id', $requestedIds->all())
            ->update([
                'attachmentable_type' => PolymorphicRelations::ATTACHMENTABLE_TASK,
                'attachmentable_id' => $this->task->id,
                'updated_at' => now(),
            ]);

        // Synchronisation means the submitted list is the desired final state.

        // Attachments currently related to the Task but omitted from the request
        // are detached, not deleted. Their stored file is also left untouched.
        $removeQuery = Attachment::query()
            ->where('attachmentable_type', PolymorphicRelations::ATTACHMENTABLE_TASK)
            ->where('attachmentable_id', $this->task->id);

        if ($requestedIds->isNotEmpty()) {
            $removeQuery->whereNotIn('_id', $requestedIds->all());
        }

        $removeQuery->update([
            'attachmentable_type' => null,
            'attachmentable_id' => null,
            'updated_at' => now(),
        ]);
    }
}
