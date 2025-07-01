<?php

namespace App\Http\Controllers;

use App\Events\UserJoinedClass;
use App\Models\Classroom;
use App\Models\UserClassroomStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{

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
        $classroom = Classroom::withCount('users as students_count')->get();

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

        if ($classroom->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'El usuario ya está inscrito en este classroom'], 409);
        }

        $classroom->users()->attach($user->id, [
            'user_name' => $user->name,
            'user_email' => $user->email,
        ]);

        UserClassroomStats::firstOrCreate([
            'user_id' => $user->id,
            'classroom_id' => $id,
        ], [
            'hp' => 100,
            'max_hp' => 100,
            'mp' => 100,
            'max_mp' => 100,
        ]);

        event(new UserJoinedClass($user, (string)$classroom->id));

        return response()->json(['message' => 'Usuario inscrito correctamente'], 201);
    }

    public function removeUserFromClassroom(Request $request, $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        $request->validate([
            'userId' => 'required|integer|exists:users,id'
        ]);

        $userToRemove = $request->input('userId');

        $authenticatedUser = Auth::user();
        if (!$authenticatedUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($classroom->professor_id !== $authenticatedUser->id) {
            return response()->json(['message' => 'No tienes permisos para expulsar usuarios de esta clase'], 403);
        }

        if (!$classroom->users()->where('user_id', $userToRemove)->exists()) {
            return response()->json(['message' => 'El usuario no está en esta clase'], 404);
        }

        $classroom->users()->detach($userToRemove);

        return response()->json(['message' => 'Usuario removido correctamente'], 200);
    }

    public function getUsersInClassroom($id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        $users = $classroom->users()->with(['classroomStats' => function ($query) use ($id) {
            $query->where('classroom_id', $id);
        }])->get();

        $usersWithStats = $users->map(function ($user) use ($id) {
            $stats = $user->classroomStats->first();

            $skinCode = $user->character ? $user->character->skin_code : 'default_warrior';

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'character_name' => $user->character ? $user->character->name : $user->name,
                'character_type' => $user->character ? $user->character->type : 'Guerrero',
                'current_skin' => $skinCode,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'hp' => $stats ? $stats->hp : 100,
                'max_hp' => $stats ? $stats->max_hp : 100,
                'mp' => $stats ? $stats->mp : 100,
                'max_mp' => $stats ? $stats->max_mp : 100,
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
        $classrooms = Classroom::where('professor_id', $professorId)
            ->withCount('users as students_count')
            ->get();

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

        if ($classroom->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'You are already enrolled in this classroom'], 409);
        }

        $classroom->users()->attach(Auth::id(), [
            'user_name' => Auth::user()->name,
            'user_email' => Auth::user()->email,
        ]);

        UserClassroomStats::firstOrCreate([
            'user_id' => Auth::id(),
            'classroom_id' => $classroom->id,
        ], [
            'hp' => 100,
            'max_hp' => 100,
            'mp' => 100,
            'max_mp' => 100,
        ]);

        $user = Auth::user();
        event(new UserJoinedClass($user, (string)$classroom->id));

        return response()->json(['message' => 'Successfully joined the classroom'], 201);
    }

    public function getMyClassrooms()
    {
        $userId = Auth::id();
        $classrooms = Classroom::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->withCount('users as students_count')->get();

        if ($classrooms->isEmpty()) {
            return response()->json(['message' => 'No classrooms found for this user'], 404);
        }

        return response()->json($classrooms, 200);
    }
}
