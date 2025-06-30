<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('classroom.{classroomId}', function ($user, $classroomId) {
    // Solo permite si el usuario pertenece a la clase
    return true; // O tu lógica real de autorización
});
