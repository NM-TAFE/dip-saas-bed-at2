<?php

declare(strict_types=1);

namespace App\Jobs\Attachments;

use App\Models\Attachment;
use App\Services\AttachmentStorageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class DeleteAttachment implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Attachment $attachment) {}

    public function handle(
        AttachmentStorageService $storage,
    ): void {
        $storage->delete($this->attachment);

        $this->attachment->delete();
    }
}
