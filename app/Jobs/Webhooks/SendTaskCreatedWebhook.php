<?php

declare(strict_types=1);

namespace App\Jobs\Webhooks;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class SendTaskCreatedWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly Task $task,
    ) {}

    public function handle(): void
    {
        $url = config('services.task_webhook.url');
        $secret = config('services.task_webhook.secret');
        if (! is_string($url) || $url === '') {
            return;
        }
        $payload = [
            'event_id' => (string) Str::ulid(),
            'event' => 'task.created',
            'data' => [
                'id' => $this->task->id,
                'name' => $this->task->name,
            ],
        ];
        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR
        );

        $signature = hash_hmac(
            'sha256',
            $json,
            (string) $secret,
        );

        Http::withHeaders([
            'X-Webhook-Signature' => $signature,
        ])
            ->withBody($json, 'application/json')
            ->post($url)
            ->throw();
    }
}
