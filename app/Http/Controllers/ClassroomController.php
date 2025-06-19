<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
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
            'start_date' => 'required|date|after_or_equal:today',
            'expiration_date' => 'required|date|after_or_equal:today|after_or_equal:start_date',
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
        if ($classroom) {
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
            $classroom->title = $request->description;
        }
        if ($request->has('max_capacity')) {
            $classroom->title = $request->max_capacity;
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

        $users = $classroom->users;
        return response()->json($users, 200); // 200: OK
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
