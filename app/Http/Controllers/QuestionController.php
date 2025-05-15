<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Question;
use App\Models\QuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{

    //Crear pregunta
    public function createQuestion(Request $request, $classroomId)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'option_1' => 'required|string|max:255',
            'option_2' => 'required|string|max:255',
            'option_3' => 'required|string|max:255',
            'option_4' => 'required|string|max:255',
            'correct_option' => 'required|string|in:option_1,option_2,option_3,option_4',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        Question::create([
            'classroom_id' => $classroomId,
            'user_id' => Auth::id(),
            'question' => $request->question,
            'option_1' => $request->option_1,
            'option_2' => $request->option_2,
            'option_3' => $request->option_3,
            'option_4' => $request->option_4,
            'correct_option' => $request->correct_option,
        ]);

        return response()->json(['message' => 'Question created successfully'], 201);
    }

    // Listar preguntas
    public function getQuestionsByClassroom($classroomId)
    {
        $questions = Question::where('classroom_id', $classroomId)->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'No questions found'], 404);
        }

        return response()->json($questions, 200);
    }

    // Responder pregunta
    public function answerQuestion(Request $request, $questionId)
{
    $validator = Validator::make($request->all(), [
        'selected_option' => 'required|string|in:option_1,option_2,option_3,option_4',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    $question = Question::find($questionId);

    if (!$question) {
        return response()->json(['message' => 'Question not found'], 404);
    }

    // Validar si el usuario ya respondió esta pregunta
    $alreadyAnswered = QuestionAnswer::where('question_id', $questionId)
        ->where('user_id', Auth::id())
        ->exists();

    if ($alreadyAnswered) {
        return response()->json(['message' => 'Ya respondiste esta pregunta.'], 409);
    }

    $isCorrect = $question->correct_option === $request->selected_option;

    // Guardar la respuesta
    QuestionAnswer::create([
        'question_id' => $questionId,
        'user_id' => Auth::id(),
        'selected_option' => $request->selected_option,
        'is_correct' => $isCorrect,
    ]);

    if ($isCorrect) {
        $character = Character::where('user_id', Auth::id())
            ->where('classroom_id', $question->classroom_id)
            ->first();
        if ($character) {
            $character->increment('level', 1);
        }
        return response()->json(['message' => '¡Respuesta correcta! Nivel aumentado.'], 200);
    } else {
        return response()->json(['message' => 'Respuesta incorrecta.'], 200);
    }
}
}
