<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ShopCharacterController;
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


    Route::controller(ShopCharacterController::class)->group(function () {
        Route::get('shop/skins', 'getShopSkins');
        Route::post('shop/purchase-skin', 'purchaseSkin');
        Route::get('shop/my-skins', 'getUserSkins');
        Route::post('character/change-skin', 'changeSkin');
        Route::get('user/gold', 'getUserGold');
    });

    Route::controller(AuthController::class)->group(function () {
        Route::get('user', 'getUser');
        Route::post('logout', 'logout');

        // Student
        Route::controller(ClassroomController::class)->group(function () {
            Route::post('classroom/join', 'joinClassroomByCode');
            Route::get('my-classrooms', 'getMyClassrooms');
            Route::get('/classroom/{id}', 'getClassroomById');

            Route::get('classroom/{id}/users', 'getUsersInClassroom');
        });


        Route::controller(QuestionController::class)->group(function () {
            Route::get('classroom/{id}/questions', 'getQuestionsByClassroom');
            Route::post('question/{id}/answer', 'answerQuestion');
            Route::get('question/{id}/check-answered', 'checkIfAnswered');
        });
    });

    Route::middleware([IsProfessor::class])->group(function () {
        Route::controller(ClassroomController::class)->group(function () {
            Route::post('classroom', 'createClassroom');

            Route::patch('/classroom/{id}', 'updateClassroomById');
            Route::delete('/classroom/{id}', 'deleteClassroomById');

            Route::post('classroom/{id}/add-user', 'addUserToClassroom');
            Route::post('classroom/{id}/remove-user', 'removeUserFromClassroom');


            //
            Route::get('classroom', 'getClassroomsByProfessor');
            //
        });

        Route::controller(CharacterController::class)->group(function () {
            Route::get('character', 'getMyCharacter');           // Mi personaje global
            Route::patch('character', 'updateCharacter');        // Actualizar mi personaje
            Route::get('classroom/{id}/characters', 'getCharactersByClassroom'); // Ver personajes en clase
        });

        Route::controller(QuestionController::class)->group(function () {
            Route::post('classroom/{id}/question', 'createQuestion');
            Route::get('question/{id}/stats', 'getQuestionStats');

            Route::patch('question/{id}/close', 'closeQuestion'); // Cerrar pregunta
            Route::get('classroom/{id}/all-questions', 'getAllQuestionsByClassroom');  // Todas las preguntas

            Route::post('classroom/{id}/reward-student', 'rewardStudent');
            Route::post('classroom/{id}/penalize-student', 'penalizeStudent');
        });
    });

    Route::middleware([IsProfessor::class])->group(function () {
        Route::get('admin-only', function () {
            return response()->json(['message' => 'Solo professor puede ver esto']);
        });
    });
});
