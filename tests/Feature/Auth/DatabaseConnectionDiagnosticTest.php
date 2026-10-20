<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('diagnoses the user model test database connection', function (): void {
    $user = new User;
    $connection = $user->getConnection();

    dump([
        'default_connection' => config('database.default'),
        'user_connection_name' => $connection->getName(),
        'user_database_name' => $connection->getDatabaseName(),
        'matching_email_count' => User::query()
            ->where('email', 'user@example.com')->count(),
    ]);

    expect($connection->getName())->toBe('mongodb');
});
