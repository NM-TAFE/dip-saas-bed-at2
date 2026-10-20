<?php

declare(strict_types=1);

use App\Http\Payloads\Attachments\NewAttachment;
use App\Jobs\Attachments\CreateNewAttachment;
use App\Jobs\Attachments\DeleteAttachment;
use App\Jobs\Attachments\UpdateAttachment;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/*
 * Real MongoDB is required. Run only against a dedicated test database.
 * These tests create uniquely named records and remove their own fixtures;
 * they intentionally do not use RefreshDatabase (SQLite cannot reset MongoDB).
 */

beforeEach(function (): void {
    $this->crudUser = User::factory()->create();
    $this->crudTask = Task::factory()->create(['assigned_to' => $this->crudUser->getKey()]);
    $this->crudAttachments = [];
    $this->actingAs($this->crudUser, 'sanctum');
});

afterEach(function (): void {
    foreach ($this->crudAttachments as $id) {
        Attachment::query()->whereKey($id)->forceDelete();
    }
    $this->crudTask->delete();
    $this->crudUser->delete();
});

function crudAttachment(User $user, Task $task, array $attributes = []): Attachment
{
    $attachment = Attachment::query()->create(array_merge([
        'attachmentable_id' => (string) $task->getKey(),
        'attachmentable_type' => 'task',
        'uploaded_by' => (string) $user->getKey(),
        'disk' => 'local',
        'path' => 'test-attachments/original.txt',
        'original_name' => 'original.txt',
        'mime_type' => 'text/plain',
        'size' => 8,
    ], $attributes));
    return $attachment;
}

function crudPayload(User $user, Task $task, array $changes = []): array
{
    return array_merge([
        'file_path' => 'test-attachments/new.txt',
        'attachmentable_id' => (string) $task->getKey(),
        'attachmentable_type' => 'task',
        'uploaded_by' => (string) $user->getKey(),
        'disk' => 'local',
        'original_name' => 'new.txt',
        'mime_type' => 'text/plain',
        'size' => 12,
    ], $changes);
}

test('authenticated client can list attachments', function (): void {
    $attachment = crudAttachment($this->crudUser, $this->crudTask);
    $this->crudAttachments[] = (string) $attachment->getKey();
    $this->getJson('/api/v1/attachments')
        ->assertOk()
        ->assertSee((string) $attachment->getKey());
});

test('authenticated client can retrieve attachment', function (): void {
    $attachment = crudAttachment($this->crudUser, $this->crudTask);
    $this->crudAttachments[] = (string) $attachment->getKey();
    $this->getJson('/api/v1/attachments/'.$attachment->getKey())
        ->assertOk()
        ->assertJsonPath('data.id', (string) $attachment->getKey())
        ->assertJsonPath('data.file_path', 'test-attachments/original.txt');
});

test('missing attachment returns 404', function (): void {
    $this->getJson('/api/v1/attachments/'.(string) \Illuminate\Support\Str::ulid())
        ->assertNotFound();
});

test('create endpoint accepts a valid attachment and dispatches its job', function (): void {
    Queue::fake();
    $this->postJson('/api/v1/attachments', crudPayload($this->crudUser, $this->crudTask))
        ->assertAccepted();
    Queue::assertPushed(CreateNewAttachment::class, function (CreateNewAttachment $job): bool {
        return $job->payload->filePath === 'test-attachments/new.txt'
            && $job->payload->attachmentableId === (string) $this->crudTask->getKey();
    });
});

test('create endpoint rejects invalid attachment metadata', function (): void {
    Queue::fake();
    $this->postJson('/api/v1/attachments', crudPayload($this->crudUser, $this->crudTask, [
        'attachmentable_type' => 'invalid',
        'size' => -1,
    ]))->assertUnprocessable()->assertJsonValidationErrors(['attachmentable_type', 'size']);
    Queue::assertNotPushed(CreateNewAttachment::class);
});

test('create job persists an attachment in MongoDB', function (): void {
    $payload = new NewAttachment(
        filePath: 'test-attachments/job-created.txt',
        attachmentableId: (string) $this->crudTask->getKey(),
        attachmentableType: 'task',
        uploadedBy: (string) $this->crudUser->getKey(),
        disk: 'local',
        originalName: 'job-created.txt',
    );
    (new CreateNewAttachment($payload))->handle();
    $created = Attachment::query()->where('path', 'test-attachments/job-created.txt')
        ->where('attachmentable_id', (string) $this->crudTask->getKey())->firstOrFail();
    $this->crudAttachments[] = (string) $created->getKey();
    expect($created->original_name)->toBe('job-created.txt');
});

test('update endpoint accepts valid metadata and dispatches its job', function (): void {
    $attachment = crudAttachment($this->crudUser, $this->crudTask);
    $this->crudAttachments[] = (string) $attachment->getKey();
    Queue::fake();
    $this->putJson('/api/v1/attachments/'.$attachment->getKey(), ['original_name' => 'renamed.txt'])
        ->assertAccepted();
    Queue::assertPushed(UpdateAttachment::class, function (UpdateAttachment $job): bool {
        return $job->payload->originalName === 'renamed.txt';
    });
});

test('update endpoint rejects invalid metadata', function (): void {
    $attachment = crudAttachment($this->crudUser, $this->crudTask);
    $this->crudAttachments[] = (string) $attachment->getKey();
    Queue::fake();
    $this->putJson('/api/v1/attachments/'.$attachment->getKey(), ['size' => -5])
        ->assertUnprocessable()->assertJsonValidationErrors('size');
    Queue::assertNotPushed(UpdateAttachment::class);
});

test('update job changes stored metadata', function (): void {
    $attachment = crudAttachment($this->crudUser, $this->crudTask);
    $this->crudAttachments[] = (string) $attachment->getKey();
    $payload = new NewAttachment(
        filePath: $attachment->path,
        attachmentableId: (string) $this->crudTask->getKey(),
        attachmentableType: 'task',
        uploadedBy: (string) $this->crudUser->getKey(),
        disk: 'local',
        originalName: 'updated-by-job.txt',
    );
    (new UpdateAttachment($attachment, $payload))->handle();
    expect($attachment->fresh()->original_name)->toBe('updated-by-job.txt');
});

test('delete endpoint accepts deletion and dispatches its job', function (): void {
    $attachment = crudAttachment($this->crudUser, $this->crudTask);
    $this->crudAttachments[] = (string) $attachment->getKey();
    Queue::fake();
    $this->deleteJson('/api/v1/attachments/'.$attachment->getKey())->assertAccepted();
    Queue::assertPushed(DeleteAttachment::class);
});

test('delete job removes stored object and soft deletes attachment', function (): void {
    Storage::fake('local');
    $attachment = crudAttachment($this->crudUser, $this->crudTask, ['path' => 'test-attachments/delete-me.txt']);
    $this->crudAttachments[] = (string) $attachment->getKey();
    Storage::disk('local')->put($attachment->path, 'example');
    (new DeleteAttachment($attachment))->handle(app(\App\Services\AttachmentStorageService::class));
    Storage::disk('local')->assertMissing('test-attachments/delete-me.txt');
    expect(Attachment::query()->find($attachment->getKey()))->toBeNull();
    expect(Attachment::withTrashed()->find($attachment->getKey()))->not->toBeNull();
});
