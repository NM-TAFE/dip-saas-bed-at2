<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Attachments;

use App\Http\Payloads\Attachments\NewAttachment;
use App\Models\Attachment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file_path' => ['sometimes', 'required', 'string'],
            'attachmentable_id' => ['sometimes', 'required', 'ulid', 'exists:tasks,_id'],
            'attachmentable_type' => ['sometimes', 'required', 'string', 'in:task'],
            'uploaded_by' => ['sometimes', 'required', 'ulid', 'exists:users,_id'],
            'disk' => ['sometimes', 'nullable', 'string', 'max:255'],
            'original_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mime_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'size' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function payload(Attachment $attachment): NewAttachment
    {
        $data = $this->validated();

        return new NewAttachment(
            filePath: (string) ($data['file_path'] ?? $attachment->path),
            attachmentableId: (string) ($data['attachmentable_id'] ?? $attachment->attachmentable_id),
            attachmentableType: (string) ($data['attachmentable_type'] ?? $attachment->attachmentable_type),
            uploadedBy: (string) ($data['uploaded_by'] ?? $attachment->uploaded_by),
            disk: $data['disk'] ?? $attachment->disk,
            originalName: $data['original_name'] ?? $attachment->original_name,
            mimeType: $data['mime_type'] ?? $attachment->mime_type,
            size: $data['size'] ?? $attachment->size,
        );
    }
}
