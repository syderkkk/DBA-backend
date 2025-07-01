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
    public function getShopSkins()
    {
        $skins = CharacterSkin::where('price', '>', 0)->get();
        return response()->json($skins, 200);
    }

    public function purchaseSkin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skin_code' => 'required|exists:character_skins,skin_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $userId = Auth::id();
        $user = User::find($userId);
        $skin = CharacterSkin::where('skin_code', $request->skin_code)->first();

        if ($user->gold < $skin->price) {
            return response()->json(['message' => 'Insufficient gold'], 400);
        }

        $existingSkin = UserSkin::where('user_id', $userId)
            ->where('skin_code', $request->skin_code)
            ->exists();

        if ($existingSkin) {
            return response()->json(['message' => 'You already own this skin'], 409);
        }

        DB::transaction(function () use ($user, $skin, $userId) {
            $user->gold = $user->gold - $skin->price;
            $user->save();

            UserSkin::create([
                'user_id' => $userId,
                'skin_code' => $skin->skin_code
            ]);
        });

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

    public function getUserSkins()
    {
        $userId = Auth::id();

        $userSkins = DB::table('user_skins')
            ->join('character_skins', 'user_skins.skin_code', '=', 'character_skins.skin_code')
            ->where('user_skins.user_id', $userId)
            ->select('user_skins.*', 'character_skins.name', 'character_skins.description', 'character_skins.price')
            ->get();

        return response()->json($userSkins, 200);
    }

    public function getShopCharacters()
    {
        $shopCharacters = DB::table('shop_characters')
            ->where('is_available', true)
            ->get();

        return response()->json($shopCharacters, 200);
    }

    public function changeSkin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skin_code' => 'required|exists:character_skins,skin_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $userId = Auth::id();

        $ownsSkin = UserSkin::where('user_id', $userId)
            ->where('skin_code', $request->skin_code)
            ->exists();

        if (!$ownsSkin) {
            return response()->json(['message' => 'You do not own this skin'], 400);
        }

        DB::transaction(function () use ($userId, $request) {
            $updated = DB::table('characters')
                ->where('user_id', $userId)
                ->update(['skin_code' => $request->skin_code]);

            if (!$updated) {
                throw new \Exception('Character not found');
            }

            DB::table('user_skins')
                ->where('user_id', $userId)
                ->update(['is_equipped' => false]);

            DB::table('user_skins')
                ->where('user_id', $userId)
                ->where('skin_code', $request->skin_code)
                ->update(['is_equipped' => true]);
        });

        return response()->json(['message' => 'Skin changed successfully'], 200);
    }

    public function getCurrentSkin()
    {
        $userId = Auth::id();

        $currentSkin = DB::table('user_skins')
            ->join('character_skins', 'user_skins.skin_code', '=', 'character_skins.skin_code')
            ->where('user_skins.user_id', $userId)
            ->where('user_skins.is_equipped', true)
            ->select(
                'user_skins.*',
                'character_skins.name',
                'character_skins.description',
                'character_skins.price'
            )
            ->first();

        if (!$currentSkin) {
            return response()->json(['message' => 'No skin equipped'], 404);
        }

        return response()->json($currentSkin, 200);
    }
}
