<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Tasks;

use App\Http\Payloads\Tasks\NewTask;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaskRequest extends FormRequest
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
            // 'project_id' => ['sometimes', 'required', 'string', 'exists:projects,id'],
            'assigned_to' => ['sometimes', 'nullable', 'string', 'exists:users,_id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['todo', 'in_progress', 'done'])],
            'due_date' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
            'tag_ids' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'tag_ids.*' => [
                'string',
            ],
        ];
    }

    public function payload(Task $task): NewTask
    {
        $data = $this->validated();

        return new NewTask(
            name: (string) ($data['name'] ?? $task->name),
            description: array_key_exists('description', $data)
                ? $data['description']
                : $task->description,
            status: (string) ($data['status'] ?? $task->status),
            dueDate: array_key_exists('due_date', $data)
                ? $data['due_date']
                : $task->due_date?->format('d-m-Y'),
            user: array_key_exists('assigned_to', $data)
                ? $data['assigned_to']
                : $task->assigned_to,
            tagIds: $data['tag_ids']
                ?? $task->tag_ids
                ?? [],
        );
    }
}
