<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSkin extends Model
{

    protected $fillable = [
        'user_id',
        'skin_code',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function characterSkin()
    {
        return $this->belongsTo(CharacterSkin::class, 'skin_code', 'skin_code');
    }
}
