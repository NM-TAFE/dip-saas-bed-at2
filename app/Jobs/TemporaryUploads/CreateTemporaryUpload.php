<?php

declare(strict_types=1);

namespace App\Jobs\TemporaryUploads;

use App\Http\Payloads\TemporaryUploads\NewTemporaryUpload;
use App\Models\TemporaryUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CreateTemporaryUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly NewTemporaryUpload $payload
    ) {}

    public function handle(): void
    {
        // Avoid creating another document if this job is retried.
        $existing = TemporaryUpload::query()
            ->whereKey($this->payload->id)
            ->first();

        if ($existing !== null) {
            return;
        }

        // Create the model using the validated payload.
        $upload = new TemporaryUpload(
            $this->payload->toArray()
        );

        // Preserve the ULID returned by the StoreController. Effectively returning "_id" & "01M4Q5G36Y2E289HE40P9Y1WFQ"
        $upload->setAttribute(
            $upload->getKeyName(),
            $this->payload->id
        );

        $upload->save();
    }
}
