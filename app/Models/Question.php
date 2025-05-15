<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'user_id',
        'question',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
        'correct_option',
        'is_active',
    ];
}
