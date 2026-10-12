<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\TemporaryUploads;

use App\Http\Payloads\TemporaryUploads\NewTemporaryUpload;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTemporaryUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'attachmentable_id' => ['required', 'ulid', 'exists:mongodb.tasks,_id'],
            'original_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:0'],
        ];
    }

    public function payload(): NewTemporaryUpload
    {
        $data = $this->validated();

        return new NewTemporaryUpload(
            id: (string) Str::ulid(),
            attachmentableId: $data['attachmentable_id'],
            originalName: $data['original_name'],
            mimeType: $data['mime_type'] ?? null,
            size: (int) $data['size'],
            uploadedBy: (string) $this->user()->getKey(),
        );
    }
}
