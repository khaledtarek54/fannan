<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->artist(),
            'type' => TransactionType::INCOME->value,
            'amount' => 100,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => TransactionType::INCOME->value]);
    }

    public function withdraw(): static
    {
        return $this->state(fn () => ['type' => TransactionType::WITHDRAW->value]);
    }
}
