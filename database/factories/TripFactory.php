<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'trip_number' => 'TR-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'boat_id' => Boat::factory(),
            'departure_port_id' => Port::factory(),
            'crew_count' => fake()->numberBetween(1, 6),
            'departure_time' => now()->addDay(),
            'planned_days' => fake()->numberBetween(1, 5),
            'gear_type' => 'سنارة',
            'status' => Trip::SCHEDULED,
            'sale_status' => Trip::SALE_NOT_STARTED,
        ];
    }

    public function forOwner(User $owner): static
    {
        return $this->state(fn () => ['owner_id' => $owner->id]);
    }

    public function captainedBy(Fisher $captain): static
    {
        return $this->state(fn () => ['captain_id' => $captain->id, 'captain_name' => $captain->name]);
    }

    public function onBoat(Boat $boat): static
    {
        return $this->state(fn () => ['boat_id' => $boat->id, 'departure_port_id' => $boat->port_id, 'owner_id' => $boat->owner_id, 'captain_id' => $boat->captain_id]);
    }

    public function atSea(): static
    {
        return $this->state(fn () => ['status' => Trip::AT_SEA, 'started_at' => now()->subDay()]);
    }

    public function readyForSale(): static
    {
        return $this->state(fn () => [
            'status' => Trip::AWAITING_APPROVAL,
            'sale_status' => Trip::SALE_OPEN,
            'started_at' => now()->subDays(2),
            'return_time' => now()->subDay(),
            'counted_at' => now()->subHours(3),
        ]);
    }
}
