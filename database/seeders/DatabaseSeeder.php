<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Italo',
            'role' => 'professor',
            'email' => 'italo@gmail.com',
            'password' => bcrypt('a322'),
        ]);

        User::factory()->create([
            'name' => 'Maricela',
            'role' => 'professor',
            'email' => 'maricela@gmail.com',
            'password' => bcrypt('a322'),
        ]);

        Classroom::factory()->create([
            'title' => 'Classroom 1',
            'description' => 'Description of classroom 1',
            'max_capacity' => 3,
            'join_code' => 'ABCDE',
            'professor_id' => 1,
        ]);
    }
}
