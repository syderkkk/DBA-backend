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

    // AGREGAR RELACIONES
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers()
    {
        return $this->hasMany(QuestionAnswer::class);
    }

    // MÉTODOS ÚTILES
    public function getCorrectAnswersCount()
    {
        return $this->answers()->where('is_correct', true)->count();
    }

    public function getTotalAnswersCount()
    {
        return $this->answers()->count();
    }

    public function getSuccessRate()
    {
        $total = $this->getTotalAnswersCount();
        if ($total === 0) return 0;
        return round(($this->getCorrectAnswersCount() / $total) * 100, 2);
    }
}
