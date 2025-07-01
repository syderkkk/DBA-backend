<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CharacterController extends Controller
{
    public function getMyCharacter()
    {
        $character = Character::with('skin')->where('user_id', Auth::id())->first();

        if (!$character) {
            return response()->json(['error' => 'Character not found'], 404);
        }

        $user = Auth::user();
        $characterData = $character->toArray();
        $characterData['user_level'] = $user->level;
        $characterData['user_experience'] = $user->experience;
        $characterData['user_gold'] = $user->gold;
        $characterData['experience_percentage'] = $this->getExperiencePercentage($user);

        return response()->json($characterData, 200);
    }

    public function getCharactersByClassroom($classroomId)
    {
        $characters = Character::with(['user', 'skin'])
            ->whereHas('user.classrooms', function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId);
            })
            ->get();

        return response()->json($characters, 200);
    }

    public function updateCharacter(Request $request)
    {
        $character = Character::where('user_id', Auth::id())->first();

        if (!$character) {
            return response()->json(['message' => 'Character not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:30',
            'skin_code' => 'sometimes|string|exists:character_skins,skin_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $character->update($request->only(['name', 'skin_code']));

        return response()->json(['message' => 'Character updated successfully'], 200);
    }

    private function getExperiencePercentage($user)
    {
        if ($user->experience_to_next_level <= 0) return 100;
        return round(($user->experience / $user->experience_to_next_level) * 100, 2);
    }
}
