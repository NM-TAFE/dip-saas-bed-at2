<?php

declare(strict_types=1);

namespace App\Jobs\Attachments;

use App\Models\Attachment;
use App\Services\AttachmentStorageService;

final readonly class DeleteAttachment
{
    public function __construct(public Attachment $attachment) {}

    // public function handle(): void
    // {
    //     $this->attachment->delete();
    // }

    public function handle(
        AttachmentStorageService $storage,
    ): void {
        $storage->delete($this->attachment);

        $this->attachment->delete();
    }
}
