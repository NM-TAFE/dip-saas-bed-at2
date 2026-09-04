<?php

declare(strict_types=1);

namespace App\Http\Api\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Attachment $resource */
final class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'file_path' => $this->resource->file_path,
            'uploaded_by' => $this->whenLoaded(
                'uploadedBy',
                fn () => $this->uploadedBy === null ? null : [
                    'id' => $this->uploadedBy->id,
                    'name' => $this->uploadedBy->name,
                ]
            ),
        ];
    }
}
