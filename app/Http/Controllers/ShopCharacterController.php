<?php

namespace App\Http\Controllers;

use App\Models\CharacterSkin;
use App\Models\User;
use App\Models\UserSkin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShopCharacterController extends Controller
{
    // Obtener todas las skins disponibles en la tienda
    public function getShopSkins()
    {
        $skins = CharacterSkin::where('price', '>', 0)->get();
        return response()->json($skins, 200);
    }

    // Comprar una skin (NO un personaje completo)
    public function purchaseSkin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skin_code' => 'required|exists:character_skins,skin_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $userId = Auth::id();
        $user = User::find($userId); // USAR FIND EN LUGAR DE Auth::user()
        $skin = CharacterSkin::where('skin_code', $request->skin_code)->first();

        if ($user->gold < $skin->price) {
            return response()->json(['message' => 'Insufficient gold'], 400);
        }

        // Verificar si ya tiene la skin
        $existingSkin = UserSkin::where('user_id', $userId)
            ->where('skin_code', $request->skin_code)
            ->exists();

        if ($existingSkin) {
            return response()->json(['message' => 'You already own this skin'], 409);
        }

        DB::transaction(function () use ($user, $skin, $userId) {
            // Descontar oro
            $user->gold = $user->gold - $skin->price;
            $user->save();

            // Agregar skin al usuario (SIN USAR RELACIÓN)
            UserSkin::create([
                'user_id' => $userId,
                'skin_code' => $skin->skin_code
            ]);
        });

        // Recargar usuario para obtener oro actualizado
        $updatedUser = User::find($userId);

        return response()->json([
            'message' => 'Skin purchased successfully',
            'remaining_gold' => $updatedUser->gold
        ], 201);
    }

    public function getUserGold()
    {
        $userId = Auth::id();
        $user = User::find($userId);
        return response()->json(['gold' => $user->gold], 200);
    }

    // Obtener skins que posee el usuario
    public function getUserSkins()
    {
        $userId = Auth::id();

        // Query manual sin usar relaciones del modelo
        $userSkins = DB::table('user_skins')
            ->join('character_skins', 'user_skins.skin_code', '=', 'character_skins.skin_code')
            ->where('user_skins.user_id', $userId)
            ->select('user_skins.*', 'character_skins.name', 'character_skins.description', 'character_skins.price')
            ->get();

        return response()->json($userSkins, 200);
    }

    public function getShopCharacters()
    {
        // Si quieres mostrar los personajes de la tabla shop_characters
        $shopCharacters = DB::table('shop_characters')
            ->where('is_available', true)
            ->get();

        return response()->json($shopCharacters, 200);
    }

    // MÉTODO ADICIONAL: Cambiar skin del personaje
    public function changeSkin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skin_code' => 'required|exists:character_skins,skin_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $userId = Auth::id();

        // Verificar que el usuario posee esta skin
        $ownsSkin = UserSkin::where('user_id', $userId)
            ->where('skin_code', $request->skin_code)
            ->exists();

        if (!$ownsSkin) {
            return response()->json(['message' => 'You do not own this skin'], 400);
        }

        // Actualizar skin del personaje (SIN USAR RELACIONES)
        $updated = DB::table('characters')
            ->where('user_id', $userId)
            ->update(['skin_code' => $request->skin_code]);

        if (!$updated) {
            return response()->json(['message' => 'Character not found'], 404);
        }

        return response()->json(['message' => 'Skin changed successfully'], 200);
    }
}
