<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'race' => fake()->randomElement(\App\Enums\CharacterRace::cases()),
            'class' => fake()->randomElement(\App\Enums\CharacterClass::cases()),
            'level' => fake()->numberBetween(1, 20),
            'strength' => fake()->numberBetween(1, 20),
            'dexterity' => fake()->numberBetween(1, 20),
            'constitution' => fake()->numberBetween(1, 20),
            'intelligence' => fake()->numberBetween(1, 20),
            'wisdom' => fake()->numberBetween(1, 20),
            'charisma' => fake()->numberBetween(1, 20),
            'hit_points' => fake()->numberBetween(10, 100),
            'armor_class' => fake()->numberBetween(10, 20),
            'speed' => 30,
            'initiative' => fake()->numberBetween(1, 20),
            'mana_points' => fake()->numberBetween(0, 50),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
