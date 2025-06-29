<?php

namespace Database\Seeders;

use App\Models\CharacterSkin;
use App\Models\Classroom;
use App\Models\User;
use Carbon\Carbon;
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
            'gold' => 100,
            'level' => 1,
            'experience' => 0,
            'experience_to_next_level' => 100,
        ]);

        User::factory()->create([
            'name' => 'Maricela',
            'role' => 'student',
            'email' => 'maricela@gmail.com',
            'password' => bcrypt('a322'),
            'gold' => 100,
            'level' => 1,
            'experience' => 0,
            'experience_to_next_level' => 100,
        ]);

        Classroom::factory()->create([
            'title' => 'Classroom 1',
            'description' => 'Description of classroom 1',
            'max_capacity' => 3,
            'join_code' => 'ABCDE',
            'professor_id' => 1,
            'start_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'expiration_date' => Carbon::now()->addMonth()->format('Y-m-d H:i:s'),
        ]);

        $this->call([
            CharacterSkinSeeder::class,
        ]);
    }

    
}
