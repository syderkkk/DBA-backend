<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserClassroomStats;

class ExperienceService
{
    public static function addExperience(User $user, int $exp = 20): array
    {
        $oldLevel = $user->level;
        $user->experience += $exp;

        // Verificar level up
        while ($user->experience >= $user->experience_to_next_level) {
            self::levelUp($user);
        }

        $user->save();

        // Actualizar stats de classroom si subió de nivel
        if ($user->level > $oldLevel) {
            self::updateUserClassroomStats($user);
        }

        return [
            'leveled_up' => $user->level > $oldLevel,
            'old_level' => $oldLevel,
            'new_level' => $user->level,
            'experience_gained' => $exp,
            'current_experience' => $user->experience,
            'experience_to_next_level' => $user->experience_to_next_level,
            'current_gold' => $user->gold,
        ];
    }

    private static function levelUp(User $user): void
    {
        $user->experience -= $user->experience_to_next_level;
        $user->level++;
        $user->experience_to_next_level = 100 + (($user->level - 1) * 25);
        $user->gold += $user->level * 10; // Oro extra por subir nivel
    }

    public static function getExperiencePercentage(User $user): float
    {
        if ($user->experience_to_next_level <= 0) return 100;
        return round(($user->experience / $user->experience_to_next_level) * 100, 2);
    }

    public static function getBaseStats(User $user): array
    {
        return [
            'base_hp' => 100 + ($user->level - 1) * 20,
            'base_mp' => 100 + ($user->level - 1) * 10,
        ];
    }

    private static function updateUserClassroomStats(User $user): void
    {
        $userStats = UserClassroomStats::where('user_id', $user->id)->get();

        foreach ($userStats as $stats) {
            // Usar los mismos valores que getBaseStats()
            $baseStats = self::getBaseStats($user);

            $stats->max_hp = $baseStats['base_hp'];
            $stats->max_mp = $baseStats['base_mp'];
            $stats->hp = min($stats->hp, $stats->max_hp);
            $stats->mp = min($stats->mp, $stats->max_mp);
            $stats->save();
        }
    }
}
