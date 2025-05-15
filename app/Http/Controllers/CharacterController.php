<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CharacterController extends Controller
{
    public function createCharacter(Request $request, $classroomId)
    {
        $userId = Auth::id();
        // Verifica si el usuario ya tiene un personaje en este classroom
        $existingCharacter = Character::where('user_id', $userId)
            ->where('classroom_id', $classroomId)
            ->first();

        if ($existingCharacter) {
            return response()->json(['error' => 'Este usuario ya tiene un Character en esta Classroom'], 409);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:30',
            'type' => 'required|string|max:10|in:Guerrero,Mago,Sanador',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        };
        
        Character::create([
            'user_id' => Auth::id(),
            'classroom_id' => $classroomId,
            'name' => $request->name,
            'type' => $request->type,
            'hp' => 100,
            'mp' => 100,
            'level' => 1,
        ]);
        return response()->json(['message' => 'Character created successfully'], 201);
    }

    public function getMyCharacter($classroomId)
    {
        $userId = Auth::id();
        $character = Character::where('user_id', $userId)
            ->where('classroom_id', $classroomId)
            ->first();
        if (!$character) {
            return response()->json(['error' => 'Character not found'], 404);
        }
        return response()->json($character, 200);
    }

    public function getCharactersByClassroom($id)
    {
        $characters = Character::where('classroom_id', $id)->get();
        return response()->json($characters, 200);
    }

    public function updateCharacterByClassroomAndId(Request $request, $classroomId, $characterId)
    {
        $character = Character::where('id', $characterId)
            ->where('classroom_id', $classroomId)
            ->first();

        if (!$character) {
            return response()->json(['message' => 'Character not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:30',
            'type' => 'sometimes|string|in:Guerrero,Mago,Sanador',
            'hp' => 'sometimes|integer|min:0',
            'mp' => 'sometimes|integer|min:0',
            'level' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $character->update($request->only(['name', 'type', 'hp', 'mp', 'level']));

        return response()->json(['message' => 'Character updated successfully'], 200);
    }

    public function deleteCharacterByClassroomAndId($classroomId, $characterId)
    {
        $character = Character::where('id', $characterId)
            ->where('classroom_id', $classroomId)
            ->first();

        if (!$character) {
            return response()->json(['message' => 'Character not found'], 404);
        }

        $character->delete();

        return response()->json(['message' => 'Character deleted successfully'], 200);
    }
}
