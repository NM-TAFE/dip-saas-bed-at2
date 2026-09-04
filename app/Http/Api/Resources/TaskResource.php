<?php

declare(strict_types=1);

namespace App\Http\Api\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Task $resource */
final class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('d-m-Y'),
            'created' => [
                'human' => $this->created_at?->diffForHumans(),
                'string' => $this->created_at?->toIso8601String(),
            ],
            'assigned_to' => $this->whenLoaded(
                'user',
                fn () => $this->user === null ? null : [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ]
            ),
            'tag_ids' => $this->tag_ids ?? [],
        ];
    }
}
