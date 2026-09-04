<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Api\Requests\Tasks;

use App\Http\Api\Requests\Tasks\UpdateTaskRequest;
use App\Models\Task;
use Tests\TestCase;

final class UpdateTaskRequestTest extends TestCase
{
    /** Checks that fields missing from the request keep their current values. */
    public function test_missing_fields_keep_existing_values(): void
    {
        $task = $this->task();

        $payload = $this->request(['name' => 'Renamed task'])->payload($task);

        self::assertSame('Renamed task', $payload->name);
        self::assertSame('Existing description', $payload->description);
        self::assertSame('01-05-2026', $payload->dueDate);
        self::assertSame('existing-user', $payload->user);
    }

    /** Checks that nullable fields can be cleared by sending null. */
    public function test_null_fields_clear_existing_values(): void
    {
        $task = $this->task();

        $payload = $this->request([
            'description' => null,
            'due_date' => null,
            'assigned_to' => null,
        ])->payload($task);

        self::assertNull($payload->description);
        self::assertNull($payload->dueDate);
        self::assertNull($payload->user);
    }

    /**
     * Creates and validates an update request using the given data.
     *
     * @param  array<string, mixed>  $data
     */
    private function request(array $data): UpdateTaskRequest
    {
        $request = UpdateTaskRequest::create('/', 'PUT', $data);
        $request->setContainer($this->app);
        $request->validateResolved();

        return $request;
    }

    /** Creates a task with the starting values used by these tests. */
    private function task(): Task
    {
        return new Task([
            'name' => 'Existing task',
            'description' => 'Existing description',
            'status' => 'todo',
            'due_date' => '2026-05-01',
            'assigned_to' => 'existing-user',
            'tag_ids' => [],
        ]);
    }
}
