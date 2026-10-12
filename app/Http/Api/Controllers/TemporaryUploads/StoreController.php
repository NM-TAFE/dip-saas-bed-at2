<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\TemporaryUploads;

use App\Http\Api\Requests\TemporaryUploads\StoreTemporaryUploadRequest;
use App\Http\Api\Responses\MessageResponse;
use App\Models\TemporaryUpload;
use App\Services\AttachmentStorageService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class StoreController
{
    public function __construct(
        private AttachmentStorageService $storage,
    ) {}

    public function __invoke(
        StoreTemporaryUploadRequest $request,
    ): MessageResponse {
        $payload = $request->payload();

        $file = $request->file('file');

        // Stage the actual file in private S3 storage.
        // We done defer() here as we need this process to completed to then Run the permanent S3 UploadJob
        // That job is the trigger
        $stagedPath = $this->storage->stage(
            file: $file,
            temporaryUploadId: $payload->id,
        );

        try {
            // Create metadata only after S3 staging succeeds.
            $upload = new TemporaryUpload([
                ...$payload->toArray(),
                'staged_path' => $stagedPath,
            ]);

            // Save the ULID returned to the client.
            $upload->setAttribute(
                $upload->getKeyName(),
                $payload->id,
            );

            $upload->save();
        } catch (Throwable $exception) {
            // Compensate for any MongoDB failure by removing the newly staged object from AWS.
            try {
                Storage::disk('s3')->delete($stagedPath);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return new MessageResponse(
            message: 'Your file has been staged for processing.',
            status: Response::HTTP_ACCEPTED,
            metadata: [
                'id' => $payload->id,
            ],
        );
    }
}
