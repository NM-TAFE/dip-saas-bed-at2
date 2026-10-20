<?php

declare(strict_types=1);

use App\Jobs\Tasks\CreateNewTask;
use App\Jobs\Tasks\DeleteTask;
use App\Jobs\Tasks\UpdateTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    // IMPORTANT: use a dedicated, isolated MongoDB testing database.
    $this->user = User::factory()->create();
    $this->task = Task::factory()->create([
        'name' => 'Original task',
        'status' => 'todo',
    ]);

    Sanctum::actingAs($this->user);

    // $token = $this->user->createToken('test-token')->plainTextToken;
    // $this->withToken($token);
});

it('accepts a request to create a task', function (): void {
    Bus::fake();

    $this->postJson('/api/v1/tasks', [
        'name' => 'New task',
        'status' => 'todo',
    ])->assertAccepted(); // 202: the queued job has not necessarily run

    Bus::assertDispatched(CreateNewTask::class);
});

it('retrieves an existing task', function (): void {
    $this->getJson('/api/v1/tasks/' . $this->task->getKey())
        ->assertOk(); // 200

    expect(Task::query()->whereKey($this->task->getKey())->exists())->toBeTrue();
});

it('accepts a request to update a task', function (): void {
    Bus::fake();

    $this->putJson('/api/v1/tasks/' . $this->task->getKey(), [
        'name' => 'Updated task',
        'status' => 'in_progress',
    ])->assertAccepted(); // 202

    Bus::assertDispatched(UpdateTask::class);
});

it('accepts a request to delete a task', function (): void {
    Bus::fake();

    $this->deleteJson('/api/v1/tasks/' . $this->task->getKey())
        ->assertAccepted(); // 202

    Bus::assertDispatched(DeleteTask::class);
});

it('requires a Sanctum-authenticated user to view a task', function (): void {
    // Clear the authentication established in beforeEach.
    auth('sanctum')->forgetUser();
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/tasks/' . $this->task->getKey())
        ->assertUnauthorized(); // 401
});
