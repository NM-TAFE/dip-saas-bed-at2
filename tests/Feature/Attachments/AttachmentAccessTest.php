<?php

declare(strict_types=1);

use Illuminate\Support\Str;

test('attachment index requires authentication', function (): void {
    $this->getJson('/api/v1/attachments')
        ->assertUnauthorized();
});

test('attachment show requires authentication', function (): void {
    $id = (string) Str::ulid();

    $this->getJson("/api/v1/attachments/{$id}")
        ->assertUnauthorized();
});

test('attachment creation requires authentication', function (): void {
    $this->postJson('/api/v1/attachments', [])
        ->assertUnauthorized();
});

test('attachment update requires authentication', function (): void {
    $id = (string) \Illuminate\Support\Str::ulid();
    $this->putJson("/api/v1/attachments/{$id}", [])
        ->assertUnauthorized();
});

test('attachment deletion requires authentication', function (): void {
    $id = (string) \Illuminate\Support\Str::ulid();
    $this->deleteJson("/api/v1/attachments/{$id}")
        ->assertUnauthorized();
});
