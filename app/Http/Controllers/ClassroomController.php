<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{
    //
    public function createClassroom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1|max:100',
            'description' => 'required|string|min:1|max:300',
            'max_capacity' => 'required|numeric',
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
}
