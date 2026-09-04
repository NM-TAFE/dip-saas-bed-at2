<?php

declare(strict_types=1);

namespace App\Jobs\Attachments;

use App\Http\Payloads\Attachments\NewAttachment;
use App\Models\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CreateNewAttachment
{

    public function __construct(public readonly NewAttachment $payload) {}

    public function handle(): void
    {
        Attachment::query()->create($this->payload->toArray());
    }
}
