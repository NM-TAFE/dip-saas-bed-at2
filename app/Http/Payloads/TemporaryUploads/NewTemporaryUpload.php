<?php

declare(strict_types=1);

namespace App\Http\Payloads\TemporaryUploads;

final readonly class NewTemporaryUpload
{
    public function __construct(
        public string $id,
        public string $attachmentableId,
        public string $originalName,
        public ?string $mimeType,
        public int $size,
        public string $uploadedBy,
    ) {}

    /**
     * @return array{
     *     attachmentable_id: string,
     *     attachmentable_type: string,
     *     uploaded_by: string,
     *     original_name: string,
     *     mime_type: ?string,
     *     size: ?int,
     *     status: ?string,
     *     expires_at: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'attachmentable_id' => $this->attachmentableId,
            'attachmentable_type' => 'task',
            'uploaded_by' => $this->uploadedBy,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ];
    }
}
