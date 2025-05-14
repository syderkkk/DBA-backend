<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'max_capacity' => $this->faker->numberBetween(10, 50),
            'join_code' => $this->faker->unique()->bothify('?????'),
            'professor_id' => 1, // Ajusta según tus datos
        ];
    }
}
