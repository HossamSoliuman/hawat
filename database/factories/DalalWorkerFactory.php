<?php

namespace Database\Factories;

use App\Models\DalalWorker;
use App\Models\DalalWorkerType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DalalWorker>
 */
class DalalWorkerFactory extends Factory
{
    protected $model = DalalWorker::class;

    public function definition(): array
    {
        return [
            'dalal_id' => User::factory()->dalal(),
            'dalal_worker_type_id' => DalalWorkerType::factory(),
            'name' => fake()->name(),
            'phone' => '05'.fake()->unique()->numerify('########'),
            'nationality' => fake()->randomElement(['سعودي', 'هندي', 'بنغلاديشي']),
            'id_number' => fake()->unique()->numerify('2#########'),
            'status' => 'نشط',
        ];
    }
}
