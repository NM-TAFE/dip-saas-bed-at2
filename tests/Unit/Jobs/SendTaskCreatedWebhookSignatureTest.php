<?php

declare(strict_types=1);

uses(\Tests\TestCase::class);

use App\Jobs\Webhooks\SendTaskCreatedWebhook;
use App\Models\Task;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('signs the exact transmitted webhook body', function (): void {
    config([
        'services.task_webhook.url' => 'https://example.test/webhook',
        'services.task_webhook.secret' => 'test-secret',
    ]);

    Http::preventStrayRequests();
    Http::fake(['https://example.test/webhook' => Http::response([], 200)]);

    $task = new Task(['name' => 'Webhook Task']);
    $task->setAttribute('_id', '01J00000000000000000000000');
    (new SendTaskCreatedWebhook($task))->handle();

    Http::assertSent(function (Request $request): bool {
        $actual = $request->header('X-Webhook-Signature')[0] ?? '';
        $expected = hash_hmac('sha256', $request->body(), 'test-secret');
        $data = $request->data();

        return $request->url() === 'https://example.test/webhook'
            && $data['event'] === 'task.created'
            && $data['data']['name'] === 'Webhook Task'
            && hash_equals($expected, $actual);
    });
});
