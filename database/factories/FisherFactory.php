<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fisher>
 */
class FisherFactory extends Factory
{
    protected $model = Fisher::class;

    public function definition(): array
    {
        return [
            'port_id' => Port::factory(),
            'name' => fake()->name(),
            'national_id' => fake()->unique()->numerify('1#########'),
            'role' => 'بحّار',
            'phone' => '05'.fake()->numerify('########'),
            'status' => 'نشط',
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this->state(fn () => ['owner_id' => $owner->id]);
    }

    /**
     * كابتن المالك — سجلّ صياد بـ is_captain بلا حساب دخول.
     */
    public function captain(): static
    {
        return $this->state(fn () => ['is_captain' => true, 'role' => Fisher::CAPTAIN_ROLE]);
    }

    public function onBoat(Boat $boat): static
    {
        return $this->state(fn () => ['boat_id' => $boat->id, 'port_id' => $boat->port_id]);
    }
}
