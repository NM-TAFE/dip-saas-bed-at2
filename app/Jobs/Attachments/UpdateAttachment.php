<?php

declare(strict_types=1);

namespace App\Jobs\Attachments;

use App\Http\Payloads\Attachments\NewAttachment;
use App\Models\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class UpdateAttachment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Attachment $attachment,
        public readonly NewAttachment $payload,
    ) {}

    public function handle(): Attachment
    {
        $this->attachment->update($this->payload->toArray());

        return $this->attachment->refresh();
    }
}
