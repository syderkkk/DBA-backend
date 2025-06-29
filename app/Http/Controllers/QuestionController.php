<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\User;
use App\Models\UserClassroomStats;
use App\Services\ExperienceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    public function createQuestion(Request $request, $classroomId)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'option_1' => 'required|string|max:255',
            'option_2' => 'required|string|max:255',
            'option_3' => 'nullable|string|max:255',
            'option_4' => 'nullable|string|max:255',
            'correct_option' => 'required|string|in:option_1,option_2,option_3,option_4',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $classroom = Classroom::find($classroomId);
        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        Question::where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $question = Question::create([
            'classroom_id' => $classroomId,
            'user_id' => Auth::id(),
            'question' => $request->question,
            'option_1' => $request->option_1,
            'option_2' => $request->option_2,
            'option_3' => $request->option_3,
            'option_4' => $request->option_4,
            'correct_option' => $request->correct_option,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Question created successfully',
            'question' => $question
        ], 201);
    }

    public function getQuestionsByClassroom($classroomId)
    {
        $questions = Question::where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'No questions found'], 404);
        }

        return response()->json($questions, 200);
    }

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

        // Verificar si la pregunta está activa
        if (!$question->is_active) {
            return response()->json(['message' => 'Esta pregunta ya no está activa'], 410);
        }

        $alreadyAnswered = QuestionAnswer::where('question_id', $questionId)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['message' => 'Ya respondiste esta pregunta.'], 409);
        }

        // Verificar si el usuario tiene maná suficiente
        $userStats = UserClassroomStats::where('user_id', Auth::id())
            ->where('classroom_id', $question->classroom_id)
            ->first();

        if (!$userStats) {
            return response()->json(['message' => 'User not enrolled in this classroom'], 404);
        }

        if (!$userStats->canAnswer()) {
            return response()->json(['message' => 'No tienes suficiente maná para responder'], 400);
        }

        $isCorrect = $question->correct_option === $request->selected_option;

        QuestionAnswer::create([
            'question_id' => $questionId,
            'user_id' => Auth::id(),
            'selected_option' => $request->selected_option,
            'is_correct' => $isCorrect,
        ]);

        // SIEMPRE restar 1 de maná por responder (correcto o incorrecto)
        $remainingMp = $userStats->useMana(1);

        if ($isCorrect) {
            // USAR EL SERVICIO EN LUGAR DEL MÉTODO DEL MODELO
            $userId = Auth::user();
            $user = User::find($userId->id);

            $result = ExperienceService::addExperience($user, 20);

            $user->gold = $user->gold + 10;
            $user->save();

            return response()->json([
                'message' => '¡Respuesta correcta! +20 EXP, +10 ORO',
                'experience_gained' => 20,
                'gold_gained' => 10,
                'current_exp' => $user->experience,
                'exp_to_next' => $user->experience_to_next_level,
                'current_gold' => $user->gold,
                'mp_used' => 1,
                'current_mp' => $remainingMp,
                'leveled_up' => $result['leveled_up'],
                'new_level' => $result['new_level'],
            ], 200);
        } else {

            // Respuesta incorrecta: -HP
            $remainingHp = $userStats->takeDamage(10);

            return response()->json([
                'message' => 'Respuesta incorrecta. -10 HP, -1 MP',
                'hp_lost' => 10,
                'mp_used' => 1,
                'current_hp' => $remainingHp,
                'current_mp' => $remainingMp,
                'max_hp' => $userStats->max_hp,
                'is_dead' => $userStats->isDead(),
            ], 200);
        }
    }

    public function getQuestionStats($questionId)
    {
        $question = Question::with(['answers'])->find($questionId);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $totalAnswers = $question->answers()->count();
        $correctAnswers = $question->answers()->where('is_correct', true)->count();
        $successRate = $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0;

        return response()->json([
            'question_id' => $question->id,
            'question_text' => $question->question,
            'total_answers' => $totalAnswers,
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $totalAnswers - $correctAnswers,
            'success_rate' => $successRate,
            'is_active' => $question->is_active,
        ], 200);
    }

    // Endpoint para cerrar/desactivar pregunta
    public function closeQuestion($questionId)
    {
        $question = Question::find($questionId);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        // Verificar que el usuario sea el profesor que creó la pregunta
        if ($question->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::table('questions')
            ->where('id', $questionId)
            ->update(['is_active' => false]);

        return response()->json(['message' => 'Question closed successfully'], 200);
    }

    // Endpoint para obtener todas las preguntas (activas e inactivas) - solo para profesores
    public function getAllQuestionsByClassroom($classroomId)
    {
        $questions = Question::where('classroom_id', $classroomId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'No questions found'], 404);
        }

        return response()->json($questions, 200);
    }

    public function checkIfAnswered($questionId)
    {
        $alreadyAnswered = QuestionAnswer::where('question_id', $questionId)
            ->where('user_id', Auth::id())
            ->exists();

        return response()->json([
            'has_answered' => $alreadyAnswered
        ], 200);
    }

    public function rewardStudent(Request $request, $classroomId)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|exists:users,id',
            'gold' => 'nullable|integer|min:1|max:100',
            'experience' => 'nullable|integer|min:1|max:50',
            'hp' => 'nullable|integer|min:1|max:50',
            'mp' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Verificar que el classroom existe
        $classroom = Classroom::find($classroomId);
        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        // Verificar que el usuario autenticado es el profesor del classroom
        if ($classroom->professor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. Only the professor can reward students'], 403);
        }

        // Verificar que el estudiante está en el classroom
        $studentId = $request->userId;
        if (!$classroom->users()->where('user_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not enrolled in this classroom'], 404);
        }

        $student = User::find($studentId);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Obtener stats del estudiante en este classroom
        $userStats = UserClassroomStats::where('user_id', $studentId)
            ->where('classroom_id', $classroomId)
            ->first();

        if (!$userStats) {
            return response()->json(['message' => 'Student stats not found for this classroom'], 404);
        }

        $rewards = [];

        // Aplicar recompensas
        if ($request->has('gold') && $request->gold > 0) {
            $student->gold += $request->gold;
            $rewards['gold_gained'] = $request->gold;
        }

        if ($request->has('experience') && $request->experience > 0) {
            $result = ExperienceService::addExperience($student, $request->experience);
            $rewards['experience_gained'] = $request->experience;
            $rewards['leveled_up'] = $result['leveled_up'];
            $rewards['new_level'] = $result['new_level'];
        }

        if ($request->has('hp') && $request->hp > 0) {
            $healedHp = $userStats->heal($request->hp);
            $rewards['hp_healed'] = $request->hp;
            $rewards['current_hp'] = $healedHp;
        }

        if ($request->has('mp') && $request->mp > 0) {
            $restoredMp = $userStats->restoreMana($request->mp);
            $rewards['mp_restored'] = $request->mp;
            $rewards['current_mp'] = $restoredMp;
        }

        $student->save();

        return response()->json([
            'message' => 'Student rewarded successfully',
            'student_name' => $student->name,
            'rewards' => $rewards,
            'current_gold' => $student->gold,
            'current_level' => $student->level,
            'current_experience' => $student->experience,
            'max_hp' => $userStats->max_hp,
            'max_mp' => $userStats->max_mp,
        ], 200);
    }

    // Función para penalizar a un estudiante
    public function penalizeStudent(Request $request, $classroomId)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|exists:users,id',
            'hp' => 'nullable|integer|min:1|max:50',
            'mp' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Verificar que el classroom existe
        $classroom = Classroom::find($classroomId);
        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        // Verificar que el usuario autenticado es el profesor del classroom
        if ($classroom->professor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. Only the professor can penalize students'], 403);
        }

        // Verificar que el estudiante está en el classroom
        $studentId = $request->userId;
        if (!$classroom->users()->where('user_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not enrolled in this classroom'], 404);
        }

        $student = User::find($studentId);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Obtener stats del estudiante en este classroom
        $userStats = UserClassroomStats::where('user_id', $studentId)
            ->where('classroom_id', $classroomId)
            ->first();

        if (!$userStats) {
            return response()->json(['message' => 'Student stats not found for this classroom'], 404);
        }

        $penalties = [];

        // Aplicar penalizaciones (SIN quitar maná como mencionaste)
        if ($request->has('hp') && $request->hp > 0) {
            $remainingHp = $userStats->takeDamage($request->hp);
            $penalties['hp_lost'] = $request->hp;
            $penalties['current_hp'] = $remainingHp;
            $penalties['is_dead'] = $userStats->isDead();
        }

        if ($request->has('mp') && $request->mp > 0) {
            $remainingMp = $userStats->useMana($request->mp);
            $penalties['mp_lost'] = $request->mp;
            $penalties['current_mp'] = $remainingMp;
        }

        return response()->json([
            'message' => 'Student penalized successfully',
            'student_name' => $student->name,
            'penalties' => $penalties,
            'max_hp' => $userStats->max_hp,
            'max_mp' => $userStats->max_mp,
        ], 200);
    }
}
