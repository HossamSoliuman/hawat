<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\BoatCategory;
use App\Models\BoatType;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Boat>
 */
class BoatFactory extends Factory
{
    protected $model = Boat::class;

    public function definition(): array
    {
        return [
            'port_id' => Port::factory(),
            'name' => 'قارب '.fake()->unique()->numerify('###'),
            'boat_number' => 'B-'.fake()->unique()->numerify('####'),
            'boat_type' => 'طراد',
            'length_m' => fake()->randomFloat(1, 6, 25),
            'crew_count' => fake()->numberBetween(1, 8),
            'status' => 'نشط',
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this->state(fn () => ['owner_id' => $owner->id, 'owner' => $owner->name]);
    }

    public function captainedBy(Fisher $captain): static
    {
        return $this->state(fn () => ['captain_id' => $captain->id, 'captain' => $captain->name]);
    }

    public function withLookups(): static
    {
        return $this->state(fn () => [
            'boat_category_id' => BoatCategory::factory(),
            'boat_type_id' => BoatType::factory(),
        ]);
    }
}
