<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterSkin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:1|max:100',
            'role' => 'required|string|in:student,professor',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:1|max:100|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        };

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'gold' => 100,
            'level' => 1,
            'experience' => 0,
            'experience_to_next_level' => 100,
        ]);

        // CREAR PERSONAJE POR DEFECTO AUTOMÁTICAMENTE
        $defaultSkin = CharacterSkin::where('is_default', true)->first();
        $skinCode = $defaultSkin ? $defaultSkin->skin_code : 'default_warrior';

        Character::create([
            'user_id' => $user->id,
            'name' => $user->name, // Usar el nombre del usuario por defecto
            'type' => 'Guerrero',  // Tipo por defecto
            'skin_code' => $skinCode,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        };

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
            return response()->json([
                'message' => 'Loggin successfully',
                'token' => $token
            ], 200);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token', $e], 500);
        }
    }

    public function getUser()
    {
        $user = Auth::user();
        return response()->json($user, 200);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'User logged out successfully'], 200);
    }
}
