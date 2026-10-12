<?php

declare(strict_types=1);

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TemporaryUploadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'attachmentable_id' => $this->resource->attachmentable_id,
            'original_name' => $this->resource->original_name,
            'mime_type' => $this->resource->mime_type,
            'size' => $this->resource->size,
            'status' => $this->resource->status,
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
        ];
    }
}
