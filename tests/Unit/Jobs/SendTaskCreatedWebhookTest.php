<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\Webhooks\SendTaskCreatedWebhook;
use App\Models\Task;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SendTaskCreatedWebhookTest extends TestCase
{
    public function test_it_sends_a_signed_webhook(): void
    {
        config([
            'services.task_webhook.url'
            => 'https://example.test/webhook',
            'services.task_webhook.secret'
            => 'test-secret',
        ]);

        Http::fake();

        $task = new Task([
            'name' => 'Webhook Task',
        ]);

        $task->setAttribute(
            '_id',
            '01J00000000000000000000000'
        );

        (new SendTaskCreatedWebhook($task))
            ->handle();
        Http::assertSent(
            function (Request $request): bool {
                $body = $request->data();

                return
                    $request->url()
                    === 'https://example.test/webhook'
                    && $body['event']
                    === 'task.created'
                    && $body['data']['name']
                    === 'Webhook Task'
                    && $request->hasHeader(
                        'X-Webhook-Signature'
                    );
            }
        );
    }
}
