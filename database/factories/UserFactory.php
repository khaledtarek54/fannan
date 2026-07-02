<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('9665########'),
            'role' => UserRole::CLIENT->value,
            'is_verified' => true,
            'completed_profile' => true,
            'email_verified_at' => now(),
            // Plaintext — the User model's setPasswordAttribute mutator hashes it once.
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    public function client(): static
    {
        return $this->state(fn () => ['role' => UserRole::CLIENT->value]);
    }

    public function artist(): static
    {
        return $this->state(fn () => ['role' => UserRole::ARTIST->value]);
    }

    /** is_admin is intentionally NOT mass-assignable, so set it directly after creation. */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['is_admin' => true])->save());
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null, 'is_verified' => false]);
    }
}
