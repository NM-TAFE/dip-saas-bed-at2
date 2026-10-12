<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $testClient = User::query()
            ->where('email', DatabaseSeeder::CLIENT_EMAIL)
            ->firstOrFail();

        Task::factory()
            ->count(20)
            ->state(fn() => [
                'assigned_to' => (string) $testClient->getKey(),
            ])
            ->create();
    }
}
