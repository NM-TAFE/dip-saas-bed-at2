<?php

declare(strict_types=1);

namespace App\Http\Payloads\Tasks;

final readonly class NewTask
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $status,
        public ?string $dueDate,
        public ?string $user,
        public array $tagIds,
    ) {}

    /**
     * @return array{
     *     name: string,
     *     description: ?string,
     *     status: string,
     *     due_date: ?string,
     *     assigned_to: ?string,
     *     tag_ids: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'assigned_to' => $this->user,
            'tag_ids' => $this->tagIds,
        ];
    }
}
