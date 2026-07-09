<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id'      => null,
            'filial_id'       => null,
            'nome'            => fake()->name(),
            'email'           => fake()->unique()->safeEmail(),
            'senha'           => static::$password ??= Hash::make('password'),
            'tipo'            => 'usuario',
            'status'          => 'ativo',
            'primeiro_acesso' => false,
            'remember_token'  => Str::random(10),
        ];
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'empresa_id' => null,
            'tipo'       => 'superadmin',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'admin',
        ]);
    }
}
