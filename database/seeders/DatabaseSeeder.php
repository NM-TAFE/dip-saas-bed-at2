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

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TaskSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test Client',
            'email' => self::CLIENT_EMAIL,
            'password' => self::CLIENT_PASSWORD,
        ]);
    }
}
