<?php

namespace Database\Seeders;

use App\Models\CharacterSkin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CharacterSkinSeeder extends Seeder
{
    public function run(): void
    {
        // Skins gratuitas por defecto
        CharacterSkin::create([
            'skin_code' => 'default_warrior',
            'name' => 'Guerrero Básico',
            'character_type' => 'Guerrero',
            'price' => 0,
            'is_default' => true,
            'description' => 'Skin básica de guerrero',
        ]);

        CharacterSkin::create([
            'skin_code' => 'default_mage',
            'name' => 'Mago Básico',
            'character_type' => 'Mago',
            'price' => 0,
            'is_default' => true,
            'description' => 'Skin básica de mago',
        ]);

        // Skins premium (solo visuales)
        CharacterSkin::create([
            'skin_code' => 'elite_warrior',
            'name' => 'Guerrero Élite',
            'character_type' => 'Guerrero',
            'price' => 50,
            'is_default' => false,
            'description' => 'Guerrero con armadura dorada',
        ]);

        CharacterSkin::create([
            'skin_code' => 'arcane_mage',
            'name' => 'Mago Arcano',
            'character_type' => 'Mago',
            'price' => 60,
            'is_default' => false,
            'description' => 'Mago con túnica mística',
        ]);
    }
}
