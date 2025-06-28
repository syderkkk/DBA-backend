<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\ShopCharacter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    // Obtener todos los personajes disponibles en la tienda
    public function getShopCharacters()
    {
        $characters = ShopCharacter::where('is_available', true)->get();
        return response()->json($characters, 200);
    }

    // Comprar un personaje para un classroom específico
    public function purchaseCharacter(Request $request, $classroomId)
    {
        $validator = Validator::make($request->all(), [
            'shop_character_id' => 'required|exists:shop_characters,id',
            'character_name' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $shopCharacter = ShopCharacter::find($request->shop_character_id);

        if (!$shopCharacter->is_available) {
            return response()->json(['message' => 'Character not available'], 400);
        }

        // Verificar si el usuario tiene suficiente oro
        if ($user->gold < $shopCharacter->price) {
            return response()->json(['message' => 'Insufficient gold'], 400);
        }

        // Verificar si el usuario ya tiene un personaje en este classroom
        $existingCharacter = Character::where('user_id', $user->id)
            ->where('classroom_id', $classroomId)
            ->first();

        if ($existingCharacter) {
            return response()->json(['message' => 'You already have a character in this classroom'], 409);
        }

        DB::transaction(function () use ($user, $shopCharacter, $classroomId, $request) {
            // Descontar oro del usuario usando DB directamente
            DB::table('users')
                ->where('id', $user->id)
                ->decrement('gold', $shopCharacter->price);

            // Crear el personaje en el classroom
            Character::create([
                'user_id' => $user->id,
                'classroom_id' => $classroomId,
                'name' => $request->character_name,
                'type' => $shopCharacter->type,
                'hp' => $shopCharacter->base_hp,
                'mp' => $shopCharacter->base_mp,
                'level' => 1,
            ]);
        });

        // Obtener el oro actualizado
        $updatedUser = User::find($user->id);

        return response()->json([
            'message' => 'Character purchased successfully',
            'remaining_gold' => $updatedUser->gold
        ], 201);
    }

    // Obtener el oro actual del usuario
    public function getUserGold()
    {
        $user = Auth::user();
        return response()->json(['gold' => $user->gold], 200);
    }
}
