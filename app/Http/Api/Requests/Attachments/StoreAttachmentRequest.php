<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Attachments;

use App\Http\Payloads\Attachments\NewAttachment;
use Illuminate\Foundation\Http\FormRequest;

final class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_path' => ['required', 'string'],
            'attachmentable_id' => ['required', 'ulid', 'exists:tasks,_id'],
            'attachmentable_type' => ['required', 'string', 'in:task'],
            'uploaded_by' => ['required', 'ulid', 'exists:users,_id'],
            'disk' => ['nullable', 'string', 'max:255'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function payload(): NewAttachment
    {
        $data = $this->validated();

        return new NewAttachment(
            filePath: (string) $data['file_path'],
            attachmentableId: (string) $data['attachmentable_id'],
            attachmentableType: (string) $data['attachmentable_type'],
            uploadedBy: (string) $data['uploaded_by'],
            disk: $data['disk'] ?? '',
            originalName: $data['original_name'] ?? '',
            mimeType: $data['mime_type'] ?? null,
            size: $data['size'] ?? null,
        );
    }
}
