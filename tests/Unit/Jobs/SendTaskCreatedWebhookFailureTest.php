<?php

declare(strict_types=1);

uses(\Tests\TestCase::class);

use App\Jobs\Webhooks\SendTaskCreatedWebhook;
use App\Models\Task;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('throws when the webhook receiver returns 503', function (): void {
    config([
        'services.task_webhook.url' => 'https://example.test/webhook',
        'services.task_webhook.secret' => 'test-secret',
    ]);
    Http::preventStrayRequests();
    Http::fake(['https://example.test/webhook' => Http::response([], 503)]);

    $task = new Task(['name' => 'Failed webhook']);
    $task->setAttribute('_id', '01J00000000000000000000000');

    expect(fn () => (new SendTaskCreatedWebhook($task))->handle())
        ->toThrow(RequestException::class);
    Http::assertSentCount(1);
});
