<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\QuestionController;
use App\Http\Middleware\IsProfessor;
use App\Http\Middleware\IsUserAuth;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// RUTAS PUBLICAS
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


// RUTAS PRIVADAS
Route::middleware([IsUserAuth::class])->group(function () {

    Route::controller(AuthController::class)->group(function () {
        Route::get('user', 'getUser');
        Route::post('logout', 'logout');
    });

    Route::middleware([IsProfessor::class])->group(function () {
        Route::controller(ClassroomController::class)->group(function () {
            Route::post('classroom', 'createClassroom');
            Route::get('/classroom/{id}', 'getClassroomById');
            Route::patch('/classroom/{id}', 'updateClassroomById');
            Route::delete('/classroom/{id}', 'deleteClassroomById');

            Route::post('classroom/{id}/add-user', 'addUserToClassroom');
            Route::post('classroom/{id}/remove-user', 'removeUserFromClassroom');
            Route::get('classroom/{id}/users', 'getUsersInClassroom');
        });

        Route::controller(CharacterController::class)->group(function () {
            Route::post('classroom/{id}/character', 'createCharacter');
            Route::get('classroom/{id}/my-character', 'getMyCharacter'); 
            Route::get('classroom/{id}/characters', 'getCharactersByClassroom');
            Route::patch('classroom/{id}/character/{characterId}', 'updateCharacterByClassroomAndId');
            Route::delete('classroom/{id}/character/{characterId}', 'deleteCharacterByClassroomAndId');
        });

        Route::controller(QuestionController::class)->group(function () {
            Route::post('classroom/{id}/question', 'createQuestion');
            Route::get('classroom/{id}/questions', 'getQuestionsByClassroom');
            Route::post('question/{id}/answer', 'answerQuestion');
        });
    });

    Route::middleware([IsProfessor::class])->group(function () {
        Route::get('admin-only', function () {
            return response()->json(['message' => 'Solo professor puede ver esto']);
        });
    });
});
