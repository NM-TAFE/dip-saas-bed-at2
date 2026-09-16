<?php

declare(strict_types=1);

namespace App\Http\Payloads\Tasks;

final readonly class SyncTaskAttachments
{
    /** @param list<string> $attachmentIds */
    public function __construct(public array $attachmentIds) {}
}
