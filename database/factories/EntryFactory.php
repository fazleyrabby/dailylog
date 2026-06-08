<?php

namespace Database\Factories;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    protected $model = Entry::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(EntryType::cases());

        return [
            'user_id' => User::factory(),
            'type' => $type->value,
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraphs(2, true),
            'body_format' => 'markdown',
            'status' => $type->defaultStatus(),
            'pinned' => false,
            'captured_via' => 'seed',
            'last_activity_at' => now(),
        ];
    }

    public function type(EntryType $type): static
    {
        return $this->state(fn () => [
            'type' => $type->value,
            'status' => $type->defaultStatus(),
            'occurred_on' => $type === EntryType::Journal ? now()->toDateString() : null,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(['pinned' => true]);
    }

    public function archived(): static
    {
        return $this->state(['archived_at' => now()]);
    }
}
