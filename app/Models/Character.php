<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'skin_code',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skin()
    {
        return $this->belongsTo(CharacterSkin::class, 'skin_code', 'skin_code');
    }

    public function getDisplayStats()
    {
        $user = $this->user;
        return [
            'name' => $this->name,
            'type' => $this->type,
            'skin_code' => $this->skin_code,
            'user_level' => $user->level,
            'user_experience' => $user->experience,
            'user_gold' => $user->gold,
        ];
    }
}
