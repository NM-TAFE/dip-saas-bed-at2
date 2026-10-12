<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public const CLIENT_EMAIL = 'client@example.com';
    public const CLIENT_PASSWORD = 'password';

    public function run(): void
    {
        // Create the test client only if it does not exist.
        User::query()->firstOrCreate(
            ['email' => self::CLIENT_EMAIL],
            [
                'name' => 'Test Client',
                'password' => self::CLIENT_PASSWORD,
            ]
        );

        // Seed Tasks after the user exists.
        $this->call([
            TaskSeeder::class,
        ]);
    }
}
