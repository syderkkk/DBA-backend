<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCharacter extends Model
{
    protected $fillable = [
        'name',
        'type',
        'base_hp',
        'base_mp',
        'price',
        'description',
        'is_available',
    ];
}
