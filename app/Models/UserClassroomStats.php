<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserClassroomStats extends Model
{
    protected $fillable = [
        'user_id',
        'classroom_id',
        'hp',
        'max_hp',
        'mp',
        'max_mp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function takeDamage(int $damage = 10): int
    {
        $this->hp = max(0, $this->hp - $damage);
        $this->save();
        return $this->hp;
    }

    public function useMana(int $cost = 1): int
    {
        $this->mp = max(0, $this->mp - $cost);
        $this->save();
        return $this->mp;
    }

    public function heal(int $amount = 25): int
    {
        $this->hp = min($this->max_hp, $this->hp + $amount);
        $this->save();
        return $this->hp;
    }

    public function restoreMana(int $amount = 10): int
    {
        $this->mp = min($this->max_mp, $this->mp + $amount);
        $this->save();
        return $this->mp;
    }

    public function isDead(): bool
    {
        return $this->hp <= 0;
    }

    public function canAnswer(): bool
    {
        return $this->mp > 0;
    }
}