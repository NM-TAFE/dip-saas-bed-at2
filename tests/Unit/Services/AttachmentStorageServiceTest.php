<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Attachment;
use App\Services\AttachmentStorageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AttachmentStorageServiceTest extends TestCase
{
    public function test_it_deletes_an_attachment_object(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put(
            'attachments/report.pdf',
            'test file',
        );

        $attachment = new Attachment([
            'disk' => 'local',
            'path' => 'attachments/report.pdf',
        ]);

        $service = new AttachmentStorageService;

        self::assertTrue(
            $service->delete($attachment)
        );

        Storage::disk('local')->assertMissing(
            'attachments/report.pdf'
        );
    }
}
