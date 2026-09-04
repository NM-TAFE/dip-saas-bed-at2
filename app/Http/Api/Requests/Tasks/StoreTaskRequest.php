<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Tasks;

use App\Http\Payloads\Tasks\NewTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTaskRequest extends FormRequest
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
            'assigned_to' => ['nullable', 'string', 'exists:users,_id'],
            'name' => ['string'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'due_date' => ['nullable', 'date_format:d-m-Y'],
            'tag_ids' => [
                'nullable',
                'array',
            ],
            'tag_ids.*' => [
                'string',
            ],
        ];
    }

    public function payload(): NewTask
    {
        $data = $this->validated();

        return new NewTask(
            name: (string) $data['name'],
            description: (string) $data['description'] ?? null,
            status: (string) $data['status'],
            dueDate: $data['due_date'] ?? null,
            user: $data['assigned_to'] ?? null,
            tagIds: $data['tag_ids'] ?? [],
        );
    }
}
