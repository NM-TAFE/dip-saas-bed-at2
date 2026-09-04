<?php

declare(strict_types=1);

namespace App\Jobs\Attachments;

use App\Http\Payloads\Attachments\NewAttachment;
use App\Models\Attachment;

final readonly class UpdateAttachment
{
    public function __construct(
        public Attachment $attachment,
        public NewAttachment $payload,
    ) {}

    public function handle(): Attachment
    {
        $this->attachment->update($this->payload->toArray());

        return $this->attachment->refresh();
    }
}
