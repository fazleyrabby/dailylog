<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 999999),
            'description' => $this->faker->sentence(),
            'status' => 'active',
            'color' => $this->faker->randomElement(['orange', 'blue', 'emerald', 'violet']),
            'last_activity_at' => now(),
        ];
    }
}
