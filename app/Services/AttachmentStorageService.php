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
}
