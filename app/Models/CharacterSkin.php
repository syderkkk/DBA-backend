<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterSkin extends Model
{
    protected $fillable = [
        'skin_code',
        'name',
        'character_type',
        'image_url',
        'description',
        'price',
        'is_available',
        'is_default',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function characters()
    {
        return $this->hasMany(Character::class, 'skin_code', 'skin_code');
    }

    public function userSkins()
    {
        return $this->hasMany(UserSkin::class, 'skin_code', 'skin_code');
    }
}
