<?php

declare(strict_types=1);

uses(\Tests\TestCase::class);

use App\Services\AttachmentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stages promotes and cleans an attachment using fake S3', function (): void {
    Storage::fake('s3');
    $service = new AttachmentStorageService;
    $uploadId = '01J00000000000000000000000';
    $taskId = '01J00000000000000000000001';
    $attachmentId = '01J00000000000000000000002';

    $staged = $service->stage(
        UploadedFile::fake()->createWithContent('example.txt', 'test content'),
        $uploadId,
    );
    Storage::disk('s3')->assertExists($staged);

    $permanent = $service->promote($staged, $taskId, $attachmentId);
    Storage::disk('s3')->assertExists($permanent);
    expect($permanent)->toBe("attachments/tasks/{$taskId}/{$attachmentId}");

    expect($service->deleteStaged($staged))->toBeTrue();
    Storage::disk('s3')->assertMissing($staged);
    Storage::disk('s3')->assertExists($permanent);
});
