<?php

namespace Database\Seeders;

use App\Models\ShopCharacter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShopCharacterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShopCharacter::create([
            'name' => 'Guerrero Élite',
            'type' => 'Guerrero',
            'base_hp' => 150,
            'base_mp' => 80,
            'price' => 50,
            'description' => 'Un guerrero poderoso con mayor resistencia',
        ]);

        ShopCharacter::create([
            'name' => 'Mago Arcano',
            'type' => 'Mago',
            'base_hp' => 80,
            'base_mp' => 200,
            'price' => 60,
            'description' => 'Un mago con enormes reservas de maná',
        ]);

        ShopCharacter::create([
            'name' => 'Sanador Divino',
            'type' => 'Sanador',
            'base_hp' => 120,
            'base_mp' => 150,
            'price' => 55,
            'description' => 'Un sanador con poderes curativos mejorados',
        ]);
    }
}
