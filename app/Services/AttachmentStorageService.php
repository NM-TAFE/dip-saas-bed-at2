<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Throwable;

final class AttachmentStorageService
{
    public function delete(Attachment $attachment): bool
    {
        if ($attachment->path === '') {
            return true;
        }
        try {
            return Storage::disk($attachment->disk)
                ->delete($attachment->path);
        } catch (Throwable $exception) {
            Log::warning(
                'Unable to delete attachment object.',
                [
                    'attachment_id' => $attachment->id,
                    'disk' => $attachment->disk,
                    'path' => $attachment->path,
                    'error' => $exception->getMessage(),
                ],
            );

            return false;
        }
    }

    /**
     * Stage an uploaded file in private S3 storage.
     *
     * @throws \RuntimeException
     */
    public function stage(
        UploadedFile $file,
        string $temporaryUploadId,
    ): string {
        $directory = "temporary-uploads/{$temporaryUploadId}";

        $path = Storage::disk('s3')->putFileAs(
            $directory,
            $file,
            'source',
            ['visibility' => 'private'],
        );

        if ($path === false) {
            throw new \RuntimeException(
                'Unable to stage the uploaded file in S3.'
            );
        }

        return $path;
    }

    /**
     * Promote a staged S3 object into permanent attachment storage.
     * If the MongoDB operation fails after the S3 copy succeeds,
     * the original staged file remains available for recovery.
     * The staged object will be deleted later, once the upload has been successfully completed.
     */
    public function promote(
        string $stagedPath,
        string $taskId,
        string $attachmentId,
    ): string {
        $permanentPath = "attachments/tasks/{$taskId}/{$attachmentId}";

        $disk = Storage::disk('s3');

        if (! $disk->exists($stagedPath)) {
            throw new \RuntimeException(
                'The staged S3 object does not exist.'
            );
        }

        if (! $disk->exists($permanentPath)) {
            $copied = $disk->copy(
                $stagedPath,
                $permanentPath,
            );

            if (! $copied) {
                throw new \RuntimeException(
                    'Unable to copy and promote the staged S3 object.'
                );
            }
        }

        return $permanentPath;
    }
}
