<?php

declare(strict_types=1);

namespace App\Jobs\TemporaryUploads;

use App\Models\Attachment;
use App\Http\Payloads\Attachments\NewAttachment;
use App\Models\TemporaryUpload;
use App\Services\AttachmentStorageService;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use RuntimeException;
use Throwable;

final class S3UploadJob implements ShouldQueue
{
    use Queueable;


    public int $tries = 3;

    public function __construct(
        public readonly string $temporaryUploadId,
    ) {}

    public function handle(
        AttachmentStorageService $storage,
    ): void {
        // Get the temporary upload.
        $upload = TemporaryUpload::query()
            ->whereKey($this->temporaryUploadId)
            ->first();

        if ($upload === null) {
            throw new RuntimeException(
                'Temporary upload not found.'
            );
        }

        // Dont process completed uploads again, early exit instead.
        if ($upload->status === 'completed') {
            return;
        }

        Log::info('Processing temporary S3 upload.', [
            'temporary_upload_id' => $this->temporaryUploadId,
            'attempt' => $this->attempts(),
        ]);

        // Mark the upload state as processing for progress communication
        $upload->status = 'processing';
        $upload->save();

        // Use the same attachment ID for retries.
        // Repeated attempts could create different S3 paths and duplicate attachment records.
        $attachmentId = $upload->attachment_id
            ?: (string) Str::ulid();

        $upload->attachment_id = $attachmentId;
        $upload->save();

        // Promote the staged file into permanent S3 storage.
        $permanentPath = $storage->promote(
            stagedPath: $upload->staged_path,
            taskId: $upload->attachmentable_id,
            attachmentId: $attachmentId,
        );

        // Check whether the attachment already exists.
        $attachment = Attachment::query()
            ->whereKey($attachmentId)
            ->first();

        if ($attachment === null) {
            // Construct the attachment using the existing MyJamJar DTO.
            $payload = new NewAttachment(
                filePath: $permanentPath,
                attachmentableId: $upload->attachmentable_id,
                attachmentableType: $upload->attachmentable_type,
                uploadedBy: $upload->uploaded_by,
                disk: 's3',
                originalName: $upload->original_name,
                mimeType: $upload->mime_type,
                size: $upload->size,
            );

            // Create the MongoDB model using the payload contract.
            $attachment = new Attachment($payload->toArray());

            // Preserve the attachment ULID across queue retries.
            $attachment->setAttribute(
                $attachment->getKeyName(),
                $attachmentId,
            );

            $attachment->save();
        };

        // Use the state to mark the upload as completed.
        $upload->status = 'completed';
        $upload->save();


        // Remove the staged S3 object after successful completion.
        $deleted = $storage->deleteStaged($upload->staged_path);

        if ($deleted) {
            Log::info('Temporary S3 upload cleaned up.', [
                'temporary_upload_id' => $upload->getKey(),
            ]);
        }
    }


    /**
     * Called when Laravel determines that the job has failed permanently.
     */
    public function failed(?Throwable $exception): void
    {
        $upload = TemporaryUpload::query()
            ->whereKey($this->temporaryUploadId)
            ->first();

        if ($upload === null) {
            return;
        }

        // Never overwrite a successfully completed upload.
        if ($upload->status === 'completed') {
            return;
        }

        $upload->status = 'failed';

        // Store a safe, general message for the API.
        $upload->error_message = 'The file could not be processed.';

        $upload->save();

        if ($exception !== null) {
            report($exception);
        }
    }
}
