<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Etablissement>
 */
class EtablissementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name' => $name,
            'description' => fake()->sentence(),
            'slug' => Str::slug($name),
            'address' => fake()->optional()->address(),
            'email' => fake()->optional()->email(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'organisation_id' => User::factory()
        ];
    }
}
