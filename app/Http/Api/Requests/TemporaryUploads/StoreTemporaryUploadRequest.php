<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\TemporaryUploads;

use App\Http\Payloads\TemporaryUploads\NewTemporaryUpload;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class StoreTemporaryUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // In the production app this would scoped to the organisation (multi-tenant)
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $taskId = $this->input('attachmentable_id');

        if (! is_string($taskId)) {
            return false;
        }

        return Task::query()
            ->whereKey($taskId)
            ->where('assigned_to', (string) $user->getKey())
            ->exists();
    }

    public function rules(): array
    {
        return [
            'attachmentable_id' => ['required', 'ulid', 'exists:mongodb.tasks,_id'],
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB, measured in KiB
            ],
        ];
    }

    public function payload(): NewTemporaryUpload
    {
        $data = $this->validated();
        $file = $this->file('file');

        return new NewTemporaryUpload(
            id: (string) Str::ulid(),
            attachmentableId: $data['attachmentable_id'],
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType(),
            size: $file->getSize(),
            uploadedBy: (string) $this->user()->getKey(),
        );
    }
}
