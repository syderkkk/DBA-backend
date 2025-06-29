<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\UserClassroomStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{
    // QR - FECHA DE INICIO Y EXPIRACION
    public function createClassroom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1|max:100',
            'description' => 'required|string|min:1|max:300',
            'max_capacity' => 'required|numeric',
            'start_date' => 'required|date_format:Y-m-d H:i',
            'expiration_date' => 'required|date_format:Y-m-d H:i|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        };

        $generatedJoinCode = strtoupper(Str::random(5));
        Classroom::create([
            'title' => $request->title,
            'description' => $request->description,
            'max_capacity' => $request->max_capacity,
            'join_code' => $generatedJoinCode,
            'professor_id' => Auth::id(),
            'start_date' => $request->start_date,
            'expiration_date' => $request->expiration_date,
        ]);

        return response()->json(['message' => 'Classroom added sucessfully'], 201);
    }

    public function getClassroom()
    {
        $classroom = Classroom::all();

        if ($classroom->isEmpty()) {
            return response(['message' => 'No classroom found'], 404);
        }

        return response()->json($classroom, 200);
    }

    public function getClassroomById($id)
    {
        $classroom = Classroom::find($id);
        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }
        return response()->json($classroom, 200);
    }

    public function updateClassroomById(Request $request, $id)
    {
        $classroom = Classroom::find($id);
        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|min:1|max:100',
            'description' => 'sometimes|string|min:1|max:300',
            'max_capacity' => 'sometimes|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        if ($request->has('title')) {
            $classroom->title = $request->title;
        }
        if ($request->has('description')) {
            $classroom->description = $request->description;
        }
        if ($request->has('max_capacity')) {
            $classroom->max_capacity = $request->max_capacity;
        }

        $classroom->update();
        return response()->json(['message' => 'Classroom updated sucessfully'], 200);
    }

    public function deleteClassroomById($id)
    {
        $classroom = Classroom::find($id);
        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }
        $classroom->delete();
        return response()->json(['message' => 'Classroom deleted sucessfully'], 200);
    }

    public function addUserToClassroom(Request $request, $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Verifica si el usuario ya está inscrito
        if ($classroom->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'El usuario ya está inscrito en este classroom'], 409);
        }

        // Adjunta el usuario al classroom con datos extra
        $classroom->users()->attach($user->id, [
            'user_name' => $user->name,
            'user_email' => $user->email,
        ]);

        // Crear stats iniciales para este classroom
        UserClassroomStats::firstOrCreate([
            'user_id' => $user->id,
            'classroom_id' => $id,
        ], [
            'hp' => 100,
            'max_hp' => 100,
            'mp' => 100,
            'max_mp' => 100,
        ]);

        return response()->json(['message' => 'Usuario inscrito correctamente'], 201);
    }

    public function removeUserFromClassroom(Request $request, $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $classroom->users()->detach($user->id);
        return response()->json(['message' => 'Usuario removido correctamente'], 200);
    }

    public function getUsersInClassroom($id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        // Obtener usuarios con sus estadísticas del classroom
        $users = $classroom->users()->with(['classroomStats' => function ($query) use ($id) {
            $query->where('classroom_id', $id);
        }])->get();

        $usersWithStats = $users->map(function ($user) use ($id) {
            $stats = $user->classroomStats->first(); // Obtener stats de este classroom

            $skinCode = $user->character ? $user->character->skin_code : 'default_warrior';

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'character_name' => $user->character ? $user->character->name : $user->name,
                'character_type' => $user->character ? $user->character->type : 'Guerrero',
                'current_skin' => $skinCode,  // ✅ CORRECTO: desde character
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                // Estadísticas del classroom específico
                'hp' => $stats ? $stats->hp : 100,
                'max_hp' => $stats ? $stats->max_hp : 100,
                'mp' => $stats ? $stats->mp : 100,
                'max_mp' => $stats ? $stats->max_mp : 100,
                // Datos globales del usuario
                'experience' => $user->experience,
                'level' => $user->level,
                'gold' => $user->gold,
            ];
        });

        return response()->json($usersWithStats, 200);
    }


    public function getClassroomsByProfessor()
    {
        $professorId = Auth::id();
        $classrooms = Classroom::where('professor_id', $professorId)->get();

        if ($classrooms->isEmpty()) {
            return response()->json(['message' => 'No classrooms found for this professor'], 404);
        }

        return response()->json($classrooms, 200);
    }

    public function joinClassroomByCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'join_code' => 'required|string|size:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $classroom = Classroom::where('join_code', $request->join_code)->first();

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        // Verifica si el usuario ya está inscrito
        if ($classroom->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'You are already enrolled in this classroom'], 409);
        }

        // Adjunta el usuario al classroom con datos extra
        $classroom->users()->attach(Auth::id(), [
            'user_name' => Auth::user()->name,
            'user_email' => Auth::user()->email,
        ]);

        // Crear stats iniciales para este classroom
        UserClassroomStats::firstOrCreate([
            'user_id' => Auth::id(),
            'classroom_id' => $classroom->id,
        ], [
            'hp' => 100,
            'max_hp' => 100,
            'mp' => 100,
            'max_mp' => 100,
        ]);

        return response()->json(['message' => 'Successfully joined the classroom'], 201);
    }

    public function getMyClassrooms()
    {
        $userId = Auth::id();
        $classrooms = Classroom::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();

        if ($classrooms->isEmpty()) {
            return response()->json(['message' => 'No classrooms found for this user'], 404);
        }

        return response()->json($classrooms, 200);
    }
}
