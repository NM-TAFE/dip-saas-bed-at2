<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Tasks;

use App\Http\Payloads\Tasks\SyncTaskAttachments as SyncTaskAttachmentsPayload;
use App\Models\Attachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;


final class SyncAttachmentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attachment_ids' => ['required', 'array'],
            'attachment_ids.*' => [
                'required',
                'ulid',
                'distinct',
            ],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var list<string> $attachmentIds */
                $attachmentIds = $this->input('attachment_ids', []);

                if ($attachmentIds === []) {
                    return;
                }

                $attachments = Attachment::withTrashed()
                    ->whereIn('_id', $attachmentIds)
                    ->get()
                    ->keyBy('id');

                foreach ($attachmentIds as $index => $attachmentId) {
                    $attachment = $attachments->get($attachmentId);

                    if ($attachment === null || $attachment->trashed()) {
                        $validator->errors()->add(
                            "attachment_ids.$index",
                            'The selected attachment is invalid.',
                        );
                    }
                }
            },
        ];
    }

    public function payload(): SyncTaskAttachmentsPayload
    {
        /** @var list<string> $attachmentIds */
        $attachmentIds = $this->input('attachment_ids', []);

        return new SyncTaskAttachmentsPayload(
            attachmentIds: array_values(array_unique($attachmentIds)),
        );
    }
}
