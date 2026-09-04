<?php

declare(strict_types=1);

namespace App\Http\Payloads\Attachments;

final readonly class NewAttachment
{
    public function __construct(
        public string $filePath,
        public string $attachmentableId,
        public string $attachmentableType,
        public string $uploadedBy,
        public string $disk = '',
        public string $originalName = '',
        public ?string $mimeType = null,
        public ?int $size = null,
    ) {}

    /**
     * @return array{
     *     attachmentable_id: string,
     *     attachmentable_type: string,
     *     uploaded_by: string,
     *     disk: string,
     *     path: string,
     *     original_name: string,
     *     mime_type: ?string,
     *     size: ?int
     * }
     */
    public function toArray(): array
    {
        return [
            'attachmentable_id' => $this->attachmentableId,
            'attachmentable_type' => $this->attachmentableType,
            'uploaded_by' => $this->uploadedBy,
            'disk' => $this->disk !== '' ? $this->disk : config('filesystems.default', 's3'),
            'path' => $this->filePath,
            'original_name' => $this->originalName !== '' ? $this->originalName : basename($this->filePath),
            'mime_type' => $this->mimeType,
            'size' => $this->size,
        ];
    }
}
