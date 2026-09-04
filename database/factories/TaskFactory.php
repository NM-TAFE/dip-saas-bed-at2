<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
final class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            // 'project_id' => Project::factory(),
            'assigned_to' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(3),
            'status' => fake()->randomElement(['todo', 'in_progress', 'done']),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'tag_ids' => [],
        ];
    }

    public function done(): static
    {
        return $this->state(fn() => ['status' => 'done']);
    }
}
