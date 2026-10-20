<?php

declare(strict_types=1);

use App\Http\Payloads\Tasks\SyncTaskAttachments as SyncPayload;
use App\Jobs\Tasks\SyncTaskAttachments;
use App\Models\Attachment;
use App\Models\Task;
use App\Support\PolymorphicRelations;
use Illuminate\Support\Str;

it('synchronises task attachments without deleting detached documents', function (): void {
    // ONLY run against a verified isolated MongoDB test database.
    $task = new Task(['name' => 'Testing synchronisation']);
    $task->setAttribute($task->getKeyName(), (string) Str::ulid());
    $task->save();

    $ids = [(string) Str::ulid(), (string) Str::ulid(), (string) Str::ulid()];

    foreach ($ids as $index => $id) {
        $attachment = new Attachment([
            'attachmentable_type' => $index < 2
                ? PolymorphicRelations::ATTACHMENTABLE_TASK : null,
            'attachmentable_id' => $index < 2 ? $task->getKey() : null,
            'disk' => 'local',
            'path' => "tests/{$id}",
            'original_name' => "file-{$index}.txt",
        ]);
        $attachment->setAttribute($attachment->getKeyName(), $id);
        $attachment->save();
    }

    try {
        (new SyncTaskAttachments(
            task: $task,
            payload: new SyncPayload(attachmentIds: [$ids[1], $ids[2], $ids[2]]),
        ))->handle();

        expect(Attachment::query()->whereKey($ids[0])->first())
            ->not->toBeNull(); // detached, not deleted
        expect(Attachment::query()->whereKey($ids[0])->first()->attachmentable_id)
            ->toBeNull();
        expect(Attachment::query()->whereKey($ids[1])->first()->attachmentable_id)
            ->toBe((string) $task->getKey());
        expect(Attachment::query()->whereKey($ids[2])->first()->attachmentable_id)
            ->toBe((string) $task->getKey());
    } finally {
        // Scoped cleanup of records created by THIS test only.
        Attachment::query()->whereIn('_id', $ids)->forceDelete();
        $task->delete();
    }
});
