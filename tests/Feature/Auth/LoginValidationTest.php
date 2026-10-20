<?php

declare(strict_types=1);

test('rejects an invalid login payload', function (): void {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'not-an-email',
        'password' => '',
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'email', 'password',
    ]);
});
