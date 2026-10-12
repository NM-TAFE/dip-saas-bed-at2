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
        // Idempotent the job executes asynchronously, it may be retried on failure.
        // We want to ensure that retries do not create duplicate TemporaryUpload records
        TemporaryUpload::query()->firstOrCreate(
            ['_id' => $this->payload->id],
            $this->payload->toArray(),
        );
    }
}
