<?php

namespace Database\Factories;

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'artist_id' => User::factory()->artist(),
            'type' => OrderType::DIRECT->value,
            'cost' => 100,
            'is_paid' => false,
        ];
    }

    public function bidding(): static
    {
        return $this->state(fn () => ['type' => OrderType::BIDDING->value]);
    }
}
