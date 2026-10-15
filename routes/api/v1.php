<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::get('/posts/{id}/comments', [CommentController::class, 'index']);

// Rutas protegidas: requieren token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    /**
     * Usuario autenticado
     *
     * Devuelve los datos del usuario dueño del token.
     *
     * @group Autenticación
     * @authenticated
     *
     * @response 200 {"id": 1, "name": "Andrés Forero", "email": "andres@test.com", "email_verified_at": null, "created_at": "2026-10-15T05:42:29.000000Z", "updated_at": "2026-10-15T05:42:29.000000Z"}
     * @response 401 scenario="Sin token" {"message": "Unauthenticated."}
     */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::post('/posts/{id}/like', [PostController::class, 'like']);
    Route::delete('/posts/{id}/like', [PostController::class, 'unlike']);
    Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
});
